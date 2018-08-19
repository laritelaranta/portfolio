<?php
namespace Chief;

class Orders_model extends Model {
	
	private $page = 1;
	private $perpage = 0;
	private $sort_column = 'id';
	private $sort_dir = 'DESC';
	private $pages = 1;
	private $extend_orders = true;
	private $calc_found_rows = true;
	
	public function setPage($page, $perpage = 50) {
		$this->page = $page;
		$this->perpage = $perpage;
		return $this;
	}
	
	public function setSort($sort_column = 'id', $sort_dir = 'DESC') {
		$this->sort_column = $sort_column;
		$this->sort_dir    = $sort_dir;
		return $this;
	}
	
	public function setExtendOrders($bool) {
		$this->extend_orders = !!$bool;
		return $this;
	}
	
	public function setCalcFoundRows($bool) {
		$this->calc_found_rows = !!$bool;
		return $this;
	}
	
	public function getPageCount() {
		return $this->pages;
	}
	
	public function getOrders($account_id = null) {
		
		$this->where('a.deleted = 0');
		if(!empty($account_id)) {
			$this->where('a.account_id = ?', $account_id);
		}
		
		$where  = $this->buildWhere();
		$offset = ($this->page - 1) * $this->perpage;
		$limit  = $this->perpage > 0 ? "LIMIT ".(int)$offset.', '.(int)$this->perpage : '';
		
		$found_rows = $this->calc_found_rows ? 'SQL_CALC_FOUND_ROWS' : '';
		
		$orders = $this->db->all("SELECT $found_rows
				a.*,
				b.name version
			FROM orders a
			LEFT JOIN versions b ON a.version_id = b.id
			WHERE $where
			ORDER BY ".$this->db->escape($this->sort_column)." ".($this->sort_dir == 'DESC' ? 'DESC' : 'ASC')."
			$limit",
			$this->arguments);
		
		$this->clearWhere();
		
		if($orders && $this->extend_orders) {
			$orders = $this->extendOrders($orders);
		}
		$rows = $this->db->one("SELECT FOUND_ROWS()");
		$this->pages = $this->perpage > 0 ? ceil($rows / $this->perpage) : 1;
		return $orders;
	}
	
	public function getOrderProducts($order_id = null) {
		$this->where('1 = 1');
		if(!empty($order_id)) {
			$this->where('a.order_id = ?', $order_id);
		}
		$where = $this->buildWhere();
		
		$order_products = $this->db->all("SELECT
				a.*,
				a.amount quantity,
				b.external_id,
				b.name,
				b.url,
				b.code,
				b.image,
				b.created,
				b.hidden,
				b.amount,
				b.price default_price,
				GROUP_CONCAT(d.id SEPARATOR '|') category_ids,
				GROUP_CONCAT(d.name SEPARATOR '|') categories
			FROM order_products a
			LEFT JOIN products b ON a.product_id = b.id
			LEFT JOIN product_categories c ON b.id = c.product_id
			LEFT JOIN categories d ON c.category_id = d.id
			WHERE $where
			GROUP BY a.id",
			$this->arguments);
		
		foreach($order_products as &$order_product) {
			if(!empty($order_product->category_ids)) {
				$keys   = explode('|', $order_product->category_ids);
				$values = explode('|', $order_product->categories);
				$count  = min(count($keys), count($values));
				$keys   = array_slice($keys,   0, $count);
				$values = array_slice($values, 0, $count);
				$order_product->categories = array_combine($keys, $values);
			}
			unset($order_product->category_ids);
		}
		
		$this->clearWhere();
		return $order_products;
	}
	
	public function extendOrders($orders) {
		
		$order_ids    = [];
		$customer_ids = [];
		foreach($orders as $order) {
			$order_ids[] = $order->id;
			$customer_ids[] = $order->customer_id;
		}
		
		if(!empty($customer_ids)) {
			$customers = [];
			$_customers = $this->where('a.id IN (...?)', $customer_ids)->getCustomers();
			foreach($_customers as $customer) {
				$customers[$customer->id] = $customer;
			}
		}
		
		if(!empty($order_ids)) {
			$order_products = [];
			$_order_products = $this->where('a.order_id IN(...?)', $order_ids)->getOrderProducts();
			foreach($_order_products as $order_product) {
				$order_products[$order_product->order_id][$order_product->id] = $order_product;
			}
		}
		
		foreach($orders as &$order) {
			$order->customer = isset($customers[$order->customer_id]) ? $customers[$order->customer_id] : [];
			$order->products = isset($order_products[$order->id]) ? $order_products[$order->id] : [];
		} unset($order);
		
		return $orders;
	}
	
	public function extendOrder($order) {
		return $this->extendOrders([$order])[0];
	}
	
	public function getCustomers() {
		$this->where('a.deleted = 0');
		$where = $this->buildWhere();
		
		$customers = $this->db->all("SELECT
				a.*,
				IF(a.allow_email_marketing, 'Kyllä', 'Ei') allow_email_marketing_str,
				IF(a.allow_phone_marketing, 'Kyllä', 'Ei') allow_phone_marketing_str,
				COUNT(b.id) orders_count,
				SUM(b.price_gross) total_spent
			FROM customers a
			LEFT JOIN orders b ON a.id = b.customer_id
			WHERE $where
			GROUP BY a.id",
			$this->arguments);
		
		$this->clearWhere();
		return $customers;
	}
	
	public function fetchMyCashflowOrders($account, $start, $resend = false) {
		
		$mycashflow_url = $this->db->one("SELECT value FROM account_settings WHERE `key` = 'mycashflow_url' AND account_id = ?", $account->id);
		$api_key        = $account->api_key;
		$orders         = [];
		$start          = is_numeric($start) ? $start : strtotime($start);
		$now            = time();
		$timestamp      = $start;
		
		if(empty($mycashflow_url) || empty($api_key) || empty($start)) return null;
		
		while(!empty($timestamp)) {
			
			Pusher::trigger('GetOrders-'.$account->id, 'Progress', [
				'progress' => ($timestamp - $start) / ($now - $timestamp) / 4
			]);
			
			$url = rtrim($mycashflow_url, '/').'/webhooks/changes?updated_after_ts='.$timestamp.'&key='.$account->api_key;
			
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			$xml = curl_exec($curl);
			
			if(empty($xml) || curl_getinfo($curl, CURLINFO_HTTP_CODE) == 404 || preg_match('~<error>~', $xml) || strtolower(substr($xml, 0, 9)) == '<!doctype') {
				Notifications::error($url);
				break;
			}
			
			Notifications::success($url);
			
			$this->db->update('accounts', ['last_polled' => date('c')], $account->id);
			
			try {
				
				$_orders = new \SimpleXMLElement($xml);
				
				$previous_timestamp = $timestamp;
				
				$_timestamp = (int)$_orders->attributes()->time_to + 1;
				if(count($_orders) == 0 || $_timestamp < $timestamp) {
					$timestamp = null;
				} else {
					$timestamp = $_timestamp;
				}
				
				foreach($_orders as $order) {
					$orders[] = $order;
				}
				
				if($timestamp == $previous_timestamp) {
					break;
				}
				
			} catch(\Exception $e) {
				Log::info('Virhe aineiston noudossa: '.$e->getMessage());
				break;
			}
		}
		
		foreach($orders as $key => $order) {
			$current = strtotime($order->OrderedAt);
			$this->saveMyCashflowOrder($account->id, $order, $resend);
			Pusher::trigger('GetOrders-'.$account->id, 'Progress', [
				'progress' => $key / count($orders) + 0.25
			]);
		}
		
		Pusher::trigger('GetOrders-'.$account->id, 'Progress', [
			'progress' => 1
		]);
		
		return $orders;
	}
	
	public function saveMyCashflowOrder($account_id, $xml = null, $resend = false) {
		
		if(empty($account_id) || empty($xml)) {
			return false;
		}
		
		# Muutetaan XML objektiksi
		if(!is_object($xml)) {
			if(file_exists($xml)) {
				$xml = file_get_contents($xml);
			}
			try {
				$_order = new \SimpleXMLElement($xml);
			} catch(\Exception $e) {
				return false;
			}
		} else {
			$_order = $xml;
		}
		
		# Tilauksen runko
		$order = (object)[
			'account_id'  => $account_id,
			'version_id'  => null,
			'external_id' => (string)$_order->OrderNumber,
			'customer_id' => null,
			'ordered'     => date('c', strtotime((string)$_order->OrderedAt)),
			'status'      => (string)$_order->OrderStatus,
			'address'     => (string)$_order->ShippingAddress->StreetAddress,
			'postcode'    => (string)$_order->ShippingAddress->City,
			'city'        => (string)$_order->ShippingAddress->ZipCode,
			'country'     => (string)$_order->ShippingAddress->Country,
			'email'	      => (string)$_order->ShippingAddress->Email,
			'price_net'   => 0,
			'vat'         => 0,
			'price_gross' => 0
		];

		# FIX: Jos Leikkien tili ja Versio 8 niin ei tallenneta MCF customer id:tä
		if($account_id == 62 && $_order->OrderVersionID == 8) {
			$ext_id = '';
		} else {
			$ext_id = (string)$_order->CustomerInformation->CustomerID;
		}
		
		$customer = (object)[
			'account_id'  => $account_id,
			'external_id' => $ext_id,
			'name'        => $_order->CustomerInformation->FirstName.' '.$_order->CustomerInformation->LastName,
			'firstname'   => (string)$_order->CustomerInformation->FirstName,
			'lastname'    => (string)$_order->CustomerInformation->LastName,
			'email'       => (string)$_order->CustomerInformation->Email,
			'address'     => (string)$_order->CustomerInformation->StreetAddress,
			'postcode'    => (string)$_order->CustomerInformation->City,
			'city'        => (string)$_order->CustomerInformation->ZipCode,
			'country'     => (string)$_order->CustomerInformation->Country,
			'locale'      => (string)$_order->OrderLanguage,
			'allow_email_marketing' => (string)$_order->CustomerInformation->Email["allowMarketing"] == "true",
			'allow_phone_marketing' => (string)$_order->CustomerInformation->Phone["allowMarketing"] == "true"
		];	
	
		$order_products = [];
		
		# Luodaan versio jos sitä ei löydy jo kannasta
		$version_id = null;
		if(!empty($_order->OrderVersionID)) {
			$version_id = $this->db->one("SELECT id FROM versions WHERE account_id = ? AND version = ?", $account_id, $_order->OrderVersionID);
			if(empty($version_id)) {
				$version_id = $this->db->insert('versions', [
					'account_id' => $account_id,
					'version'    => $_order->OrderVersionID
				]);
			}
		}
		$order->version_id = $version_id;
		
		# Noudetaan MyCashflow-kaupan osoite tilin asetuksista
		$host = reset(array_filter(explode(',', $this->db->one("SELECT value FROM account_settings WHERE account_id = ? AND `group` = 'general' AND `key` = 'mycashflow_url'", $account_id))));
		
		# Varmistetaan että osoite alkaa aina http://
		if(strpos($host, 'http') !== false) {
			$host = 'http://'.parse_url($host, PHP_URL_HOST);
		} else {
			$host = 'http://'.$host;
		}
		
		$skip_images = false;
		
		# Käydään tilauksen raakamuotoiset tuotteet läpi ja tallennetaan ne kantaan
		foreach($_order->Products->Product as $_product) {
			
			$product_id   = (string)$_product->ProductID;
			$product_name = (string)$_product->ProductName;
			$product_url  = $host.'/product/'.$product_id.'/';
			
			if($product_id == 'SHIPPING') {
				$product_id   = -1;
				$product_name = 'SHIPPING';
				$product_url  = '';
			}
			
			if($product_id == 'PAYMENT') {
				$product_id   = -2;
				$product_name = 'PAYMENT';
				$product_url  = '';
			}
			
			if(is_numeric($product_id)) {
				
				$product = (object)[
					'account_id'  => $account_id,
					'external_id' => $product_id,
					'name'        => $product_name,
					'url'         => $product_url
				];
				
				if(!$skip_images) {
					# Tarkastetaan löytyykö tälle tuotteelle jo tallennettua kuvaa
					$product->image = $this->db->one("SELECT image FROM products WHERE account_id = ? AND external_id = ?", $account_id, $product_id);
					if(empty($product->image) || !file_exists($product->image)) {
						$product->image = null;
					}
					
					if(empty($product->image) && !empty($host) && $product_id > 0) {
						# Tuotekuvien osoitteet saadaan interface-kyselyn kautta kaupasta
						$image_html = @file_get_contents($host.'/interface/ProductImage?setProduct='.$product_id);
						if($image_html) {
							# Tuotteet ovat <a href="#"><img> muodossa, etsitään href-attribuuteista täysikokoisten kuvien osoitteet
							preg_match('~href="(.*?)"~', $image_html, $match);
							# Tallennetaan vain ensimmäinen tulos
							if(isset($match[1])) {
								$image = $host.$match[1];
								$path  = 'uploads/'.$account_id.'/'.sha1($image).'.jpg';
								if(file_exists($path)) {
									$product->image = $path;
								} else {
									# Noudetaan aina HTTPS:n yli ja varmistetaan että kuva on olemassa ennen lataamista
									$image   = str_replace('http:', 'https:', $image);
									$headers = @get_headers($image);
									if(isset($headers[0]) && strpos($headers[0], 404) === false) {
										$file = @file_get_contents($image);
										if($file) {
											$resource = imagecreatefromstring($file);
											# Tallennetaan kuva levylle
											if($resource) {
												if(!is_dir('uploads/'.$account_id.'/')) {
													mkdir('uploads/'.$account_id.'/', 0777, true);
												}
												imagejpeg($resource, $path, 85);
												$product->image = $path;
											}
										}
									}
								}
							}
						}
					}
				}
				
				# Luodaan uusi tuote tietokantaan jos sitä ei ole jo olemassa
				$product_id = $this->db->one("SELECT
						id
					FROM products
					WHERE account_id = ?
					AND external_id = ?",
					$product->account_id,
					$product->external_id);
				
				if(!$product_id) {
					$product->created    = date('c');
					$product_id = $this->db->insert('products', $product);
				} else {
					$this->db->update('products', $product, $product_id);
				}
				
				if(!empty($version_id) && !$this->db->one("SELECT id FROM product_versions WHERE product_id = ? AND version_id = ?", $product_id, $version_id)) {
					$this->db->insert('product_versions', [
						'product_id' => $product_id,
						'version_id' => $version_id
					]);
				}
				
				# Päivitetään tuotteen kategoriat ja lisätään puuttuvat kategoriat kantaan
				$this->db->query("DELETE FROM product_categories WHERE product_id = ?", $product_id);
				if(!empty($_product->Categories->Category)) {
					foreach($_product->Categories->Category as $category) {
						# <Category id="123>Nimi</Category>
						$external_id = (string)$category['id'];
						$category    = (string)$category;
						$category_id = $this->db->one("SELECT id FROM categories WHERE external_id = ? AND account_id = ?", $external_id, $account_id);
						if(!$category_id) {
							$category_id = $this->db->insert('categories', [
								'account_id'  => $account_id,
								'external_id' => $external_id,
								'name'        => $category,
								'slug'        => Common::slug($category),
								'sort'        => max(1, $this->db->one("SELECT MAX(sort) + 1 FROM categories WHERE account_id = ? AND parent_id IS NULL", $account_id))
							]);
						}
						$this->db->insert('product_categories', [
							'product_id'  => $product_id,
							'category_id' => $category_id
						]);
					}
				}
				
				$product_variation_id = null;
				
				if(isset($_product->Variation)) {
					$variation_id   = isset($_product->Variation['id']) ? (string)$_product->Variation['id'] : '1';
					$variation_name = isset($_product->Variation) ? (string)$_product->Variation : $product->name;
					
					$product_variation_id = $this->db->one("SELECT id FROM product_variations WHERE product_id = ? AND external_id = ?", $product_id, $variation_id);
					if(!$product_variation_id) {
						$product_variation_id = $this->db->insert('product_variations', [
							'product_id'  => $product_id,
							'external_id' => $variation_id,
							'name'        => $variation_name
						]);
					}
				}
				
				$order_products_key = empty($product_variation_id) ? $product_id : $product_id.'-'.$product_variation_id;
				
				# Sama tuote voi olla useampaan kertaan tilauksella, kantaan tallennetaan vain yksi instanssi jokaista.
				if(!isset($order_products[$order_products_key])) {
					$order_products[$order_products_key] = (object)[
						'product_id'           => $product_id,
						'product_variation_id' => $product_variation_id,
						'amount'               => 0,
						'price_net'            => 0,
						'vat'                  => 0,
						'price_gross'          => 0
					];
				}
				
				$amount      = (int)$_product->Quantity;
				$price_gross = (float)$_product->UnitPrice;
				$vat         = (float)$_product->UnitTax;
				$price_net   = $price_gross - $vat;
				
				$order_products[$order_products_key]->amount     += $amount;
				$order_products[$order_products_key]->price_net   = $price_net;
				$order_products[$order_products_key]->vat         = $vat;
				$order_products[$order_products_key]->price_gross = $price_gross;
				
				$order->price_net   += $price_net * $amount;
				$order->price_gross += $price_gross * $amount;
			}
		}
		
		$order->vat = $order->price_gross - $order->price_net;
		
		# MyCashflowssa voi olla asiakasnumerottomia asiakkaita, merkataan näille null external_id:ksi
		if((int)$customer->external_id == 0) {
			$customer->external_id = null;
		}

		# Etsitään olemassaolevaa asiakasta joko external_id:llä tai sähköpostiosoitteella
		$customer_id = $this->db->one("SELECT
				id
			FROM customers
			WHERE account_id = ?
			AND (external_id = ? AND 0 != ? OR email = ?)",
			$account_id,
			$customer->external_id,
			(int)$customer->external_id,
			$customer->email);
		
		# Oletuksena asiakkaan kieli on suomi
		if(empty($customer->locale)) {
			$customer->locale = 'fi_FI';
		}
		
		# Muutetaan asiakkaan locale pitempään muotoon tarvittaessa
		$locale_replacements = [
			'fi' => 'fi_FI',
			'en' => 'en_US',
			'se' => 'sv_SE'
		];
		
		if(isset($locale_replacements[$customer->locale])) {
			$customer->locale = $locale_replacements[$customer->locale];
		} elseif(strlen($customer->locale) == 2) {
			$customer->locale = strtolower($customer->locale).'_'.strtoupper($customer->locale);
		}
		
		# Tallennetaan asiakas
		if(!$customer_id) {
			$customer_id = $this->db->insert('customers', $customer);
		} else {
			$this->db->update('customers', $customer, $customer_id);
		}
		
		$order->customer_id = $customer_id;
		
		$existing_order = $this->db->row("SELECT
				id,
				status
			FROM orders
			WHERE account_id = ?
			AND external_id = ?",
			$account_id,
			$order->external_id);
		
		# Tallennetaan tilaus
		if($existing_order) {
			if($existing_order->status == 'Sent' && !$resend) {
				$order->status = 'Sent';
			}
			$this->db->update('orders', $order, $existing_order->id);
			$order_id = $existing_order->id;
		} else {
			$order->created = date('c');
			$order_id = $this->db->insert('orders', $order);
		}
		
		$filename = sha1($order->account_id.'-'.$order_id.'-xml').'.xml';
		$path = 'uploads/'.$order->account_id.'/xml/'.substr($filename, 0, 2).'/';
		if(!is_dir($path)) {
			mkdir($path, 0777, true);
		}
		
		if(file_put_contents($path.$filename, $_order->asXML())) {
			$this->db->update('orders', [
				'xml_path' => $path.$filename
			], $order_id);
		}
		
		# Päivitetään tilauksen tuotteet kantaan
		$this->db->query("DELETE FROM order_products WHERE order_id = ?", $order_id);
		foreach($order_products as $order_product) {
			$order_product->order_id = $order_id;
			$this->db->insert('order_products', $order_product);
		}
		
		return true;
	}
	
	public function export($account_id) {
		$account = $this->model('accounts')->getAccount($account_id);
		if(!empty($account->settings->mailchimp_api_key)) {
			$this->exportMailchimp($account);
		}
		if($account_id == 9) {
			$this->exportInfocloud($account);
		}
	}
	
	private function exportInfocloud($account) {
		
		Account::init($this->db, $account->id);
		
		$infocloud = $this->model('infocloud');
		
		$page      = 1;
		$perpage   = 250;
		
		$setting = $this->db->row("SELECT id, value FROM account_settings WHERE account_id = ? AND `group` = 'infocloud' AND `key` = 'last_exported'", $account->id);
		$last_exported = empty($setting) ? null : $setting->value;
		
		while(true) {
			
			$start = max($last_exported, 14493);
			$this->where('a.account_id = ?', $account->id);
			$this->where("a.external_id > ?", $start);
			$this->setPage($page, $perpage);
			$orders = $this->getOrders();
			
			if(empty($orders)) {
				break;
			}
			
			foreach($orders as $order) {
				$last_exported = max($order->external_id, $last_exported);
			}
			
			echo (($page - 1) * $perpage).' - '.((($page - 1) * $perpage) + count($orders))."\n";
			$page++;
			
			if(count($orders)) {
				$infocloud->sendOrders($account, $orders);
			} elseif(count($orders) < $perpage) {
				break;
			}
		}
		
		if(!empty($last_exported)) {
			if(!empty($setting->id)) {
				$this->db->update('account_settings', [
					'value' => $last_exported
				], $setting->id);
			} else {
				$this->db->insert('account_settings', [
					'account_id' => $account->id,
					'group'      => 'infocloud',
					'key'        => 'last_exported',
					'value'      => $last_exported
				]);
			}
		}
	}
	
	private function exportMailchimp($account) {
		
		Account::init($this->db, $account->id);
		$settings  = Account::get('settings');
		$mailchimp = $this->model('mailchimp');
		
		$page      = 1;
		$perpage   = 250;
		
		while(true) {
			
			$this->where('a.account_id = ?', $account->id);
			$this->where("a.status != 'Sent'");
			
			if(!isset($settings->mailchimp_export_trigger) || $settings->mailchimp_export_trigger == 'shipped') {
				$this->where("a.status = 'Shipped'");
			}
				
			$this->setPage($page, $perpage);
			$orders = $this->getOrders();
			
			if(empty($orders)) {
				break;
			}
			
			echo (($page - 1) * $perpage).' - '.((($page - 1) * $perpage) + count($orders))."\n";
			$page++;
			
			if(count($orders)) {
				$mailchimp->sendOrders($account, $orders);
			} elseif(count($orders) < $perpage) {
				break;
			}
		}
	}
}
