<?php

$end_time = strftime("%Y-%m-%d", time());

// Checks if any contracts have ended and changes the status if so
$q_employees = mysql_query("SELECT * FROM clients_employees WHERE end_time < '".$end_time."' AND muistutus = 1");

while($row_employees = mysql_fetch_array($q_employees)) {
	mysql_query("UPDATE employees SET status = '1' WHERE employee_id = ".$row_employees['employee_id']."") or die(mysql_error());
}

	
// Checks if hours haven't been sent in last two weeks and sends an email if so
$q_employee_hours = mysql_query("SELECT * FROM employees WHERE halytys = 1");

while($row_employee_hours = mysql_fetch_array($q_employee_hours)) {
	
	$q_tyoaika = mysql_query("SELECT * FROM hours WHERE start_date >= '".$vertailuaika_pvm."' AND start_date <= '".$aikaloppu_pvm."' AND employee_id = ".$row_employee_hours['employee_id']."");
	$tyoaika_num = mysql_num_rows($q_tyoaika);
	
	if(!$tyoaika_num) {
		$firstname = mysql_result(mysql_query("SELECT firstname FROM employees WHERE employee_id = ".$row_employee_hours['employee_id'].""),0);
		$surname = mysql_result(mysql_query("SELECT surname FROM employees WHERE employee_id = ".$row_employee_hours['employee_id'].""),0);

		// Sends email
		$headers = "MIME-Version: 1.0\r\n";
		$headers .= "From: CRM <no-reply@crm.com>\r\n";
		//add boundary string and mime type specification
		$headers .= "Content-Type: text/html; charset=utf-8\r\n";
		$headers .= "Content-Transfer-Encoding: 7bit\r\n";
		//define the body of the message.
		ob_start(); //Turn on output buffering
		?>

		<span style="font-family: Arial; font-size: 12px;">
		This employee hasn't updated his hours last week:
		<br/><br/>
		<?php print("Name: " . $firstname . " " . $surname); ?><br/>
		<br/>
		</span>
		
		<?php
		//copy current buffer contents into $message variable and delete current output buffer
		$message = ob_get_clean();
		
	}
	
}

?>