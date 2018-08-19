<?php namespace Chief; ?>
<h1>Tilaukset</h1>
<div class="horizontal" style="margin-bottom: 20px;">
	<?php if(User::is('admin')): ?>
		<form method="post" action="<?=BASE_DIR?>orders/import/">
			<label>Päivitä tilausdata <input style="width: 100px;" value="<?=date('j.n.Y', strtotime('-30 days'))?>" type="text" class="date" name="date"> alkaen</label>
			<?php if(User::is('admin')): ?>	
				<label><input type="checkbox" name="resend"> Lähetä tilaukset uudestaan MailChimpiin</label>
			<?php endif; ?>
			<input class="btn" type="submit" name="fetch" value="Nouda">
		</form>
	<?php endif; ?>
</div>
<div class="import-progress hidden" data-account_id="<?=ACCOUNT_ID?>">
	<div class="import-progress-inner"></div>
</div>
<?php if($last_page > 0): ?>
	<?=Utils::pagination('orders/main/{page}/{perpage}/'.$sort_column.'/'.$sort_dir.'/', $page, $last_page, $perpage)?>
<?php endif; ?>
<?=$table?>
<?php if($last_page > 0): ?>
	<?=Utils::pagination('orders/main/{page}/{perpage}/'.$sort_column.'/'.$sort_dir.'/', $page, $last_page, $perpage)?>
<?php endif; ?>
