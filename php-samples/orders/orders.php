<?php
namespace Chief;

class Orders extends Controller {
	
	public function main($page = 1, $perpage = 50, $sort_column = 'external_id', $sort_dir = 'DESC') {
		Layout::js(BASE_DIR.'modules/orders/orders.js', 5);
		$orders = $this->model('orders');
		
		$fields = ['id', 'external_id', 'created', 'status_str', 'products_ordered', 'allow_email_marketing_str', 'allow_phone_marketing_str'];
		
		$sort_column = in_array($sort_column, $fields) ? $sort_column : 'external_id';
		$sort_dir    = strtoupper($sort_dir) == 'ASC' ? $sort_dir : 'DESC';
		
		$db_sort_column = $sort_column;
		if($sort_column == 'status_str') {
			$db_sort_column = 'status';
		}
		
		$count = $this->db->count("SELECT id FROM orders WHERE account_id = ?", ACCOUNT_ID);
		
		$data['page']      = $page;
		$data['perpage']   = $perpage;
		$data['last_page'] = ceil($count / $perpage);
		
		$data['sort_column'] = $sort_column;
		$data['sort_dir'] = $sort_dir;
		
		$data['orders'] = $orders->setPage($page, $perpage)->setSort($db_sort_column, $sort_dir)->getOrders(ACCOUNT_ID);
		
		$status_map = array(
			'Open'      => ['Avoinna', 'grey'],
			'Cancelled' => ['Peruttu', 'red'],
			'Shipped'   => ['Odottaa', 'yellow'],
			'Sent'      => ['Siirretty', 'green']
		);
		
		$data['table'] = $this->plugin('table')
			->setData($data['orders'])
			->setSort($sort_column, $sort_dir, BASE_DIR.'orders/main/'.$data['page'].'/'.$data['perpage'].'/{sort_column}/{sort_direction}/')
			->action('refresh', 'orders/update/{external_id}/')
			->column('external_id', 'Tilaus')->width(80)
			->column('status', 'Tila')->transform(function($status) use($status_map) {
				return '<span class="status status-'.$status_map[$status][1].'"></span>'.$status_map[$status][0];
			})->width(90)
			->column('version', 'Versio')->width(150)
			->column('ordered', 'Tilattu')->transform(function($date) {
				return date('j.n.Y H:i', strtotime($date));
			})->width(120)
			->column('customer_email', 'Asiakas')->width(250)->transform(function($_, $row) {
				// Shows ShippingAddress email instead of Customer email if defined
				if($row->email) {
					return $row->email;
				} else {
					return $row->customer->email;
				}
			})
			->column('allow_email_marketing_str', 'Email')->width(100)->transform(function($_, $row) {
				return $row->customer->allow_email_marketing_str;
			})
			->column('allow_phone_marketing_str', 'Puhelin')->width(100)->transform(function($_, $row) {
				return $row->customer->allow_phone_marketing_str;
			});
			
		$this->view($data);
	}
	
	public function import() {
		$date = \DateTime::createFromFormat('j.n.Y', $_POST['date']);
		$resend = User::is('admin') && isset($_POST['resend']);
		if($date) {
			$command = "/usr/bin/php /var/www/flowchimp/cron/cron.php PollChanges ".ACCOUNT_ID." ".$date->format('Y-m-d')." ".($resend ? 'resend' : '')." >> /dev/null &";
			Notifications::success('Massasiirto aloitettu');
		} else {
			Notifications::error('Massasiirron aloitus epäonnistui, tarkista päivämäärä');
		}
		$this->redirect('orders/main/');
	}
	
	public function update($id) {
		Benchmark::start();
		$orders_model   = $this->model('orders');
		$mycashflow_url = $this->db->one("SELECT value FROM account_settings WHERE `key` = 'mycashflow_url' AND account_id = ?", ACCOUNT_ID);
		$url            = $mycashflow_url.'webhooks/get?order='.$id.'&key='.User::get('account')->api_key;
		$orders = new \SimpleXMLElement(file_get_contents($url));
		
		foreach($orders as $order) {
			$current = $this->db->row("SELECT * FROM orders WHERE account_id = ? AND external_id = ?", ACCOUNT_ID, (int)$order->OrderNumber);
			$orders_model->saveMyCashflowOrder(ACCOUNT_ID, $order);
			if($current->status != 'Sent' && $current->status != (string)$order->OrderStatus) {
				Notifications::success('Päivitetty tilauksen '.$order->OrderNumber.' tila '.$current->status.' -> '.(string)$order->OrderStatus);
			} else {
				Notifications::error('Tilaukselle '.$order->OrderNumber.' ei löytynyt päivitettyjä tietoja.');
			}
		}
		$this->redirect();
	}
	
	public function resave($id) {
		$orders = $this->model('orders');
		$order = $orders->where('a.id = ?', $id)->getOrders();
		if($order) {
			$order = $order[0];
			$orders->saveMyCashflowOrder($order->account_id, $order->xml);
		}
	}
	
	public function unsubscribed($page = 1) {
		
		$data['unsubscribed'] = $this->model('orders')->getUnsubscribed();
		
		$perpage = 50;
		$pages   = ceil(count($data['unsubscribed']) / $perpage);
		$page    = max(1, min($pages, $page));
		
		$data['unsubscribed'] = array_slice($data['unsubscribed'], ($page - 1) * $perpage, $perpage);
		
		$data['table'] = $this->plugin('table')
			->setData($data['unsubscribed'])
			->setNavigation($page, $pages, BASE_DIR.'orders/unsubscribed/{page}/')
			->column('datetime', Translate::text('Päiväys'))->dateFormat('j.n.Y H:i')->width(200)
			->column('email', Translate::text('Sähköpostiosoite'));
		
		$this->view($data);
	}
	
	public function test_mailchimp($id) {
		header('Content-type: text/plain');
		$orders = $this->model('orders')->where('a.id = ?', $id)->getOrders();
		Account::init($this->db, reset($orders)->account_id);
		print_r($this->model('mailchimp')->sendOrders(Account::get(), $orders));
	}
	
	public function get_order($order_id = null) {
		$this->model('mailchimp')->getOrder($order_id);
	}
	
	public function get_product($product_id = null) {
		$this->model('mailchimp')->getProduct($product_id);
	}
	
	public function dump_batch($batch_id) {
		$this->model('mailchimp')->parseBatchResponse($batch_id);
	}
}
