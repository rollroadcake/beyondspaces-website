<?php
$sEventTitleVar    = '$sEventTitle';
?>
<!DOCTYPE html>
<html lang="uk">
	<head>
		<link href='http://fonts.googleapis.com/css?family=Lato:300,400' rel='stylesheet' type='text/css'>
	</head>
<body style="margin: 0;padding: 0;border: 0;border-top: 8px solid #5fc7ae;border-bottom: 8px solid #5fc7ae; font-size: 100%;font: inherit;vertical-align: baseline;background:transparent;font-family: 'Lato', sans-serif; font-size: 20px;color:#7f7f7f;font-weight:300; ">
	<div class="wrapper" style="display: block;margin:0 auto;width:600px;text-align:center;padding-top: 45px;padding-bottom: 65px;">
		<h1 style="margin:0;color:#333333;font-size: 56px; font-weight:300;"><?php esc_html_e( 'Hello!', 'coworking' ) ?></h1>
		<p style="line-height: 34px;margin-bottom:50px;"><?php echo sprintf( esc_html__( 'A new client has just registered for event %1$s %2$s', 'coworking' ), '<br>', $sEventTitleVar ) ?>, <br> $sEventDate</p>
		<div class="eventDesc" style="width: 330px;margin: 0 auto 50px;overflow: hidden;-webkit-border-radius: 3px;-moz-border-radius: 3px;border-radius: 3px;">
			<p style="line-height: 60px;margin: 0;border-bottom: 1px solid #6dd2ba; background: #5fc7ae;font-size: 16px;color:#fff;font-weight:300;text-align:center;">$sClientName</p>
			<p style="line-height: 60px;margin: 0;border-bottom: 1px solid #6dd2ba; background: #5fc7ae;font-size: 16px;color:#fff;font-weight:300;text-align:center;"><?php esc_html_e( 'tel', 'coworking' ) ?>: $sClientTel</p>
			<p style="line-height: 60px;padding:8px 0; margin: 0;background: #5fc7ae;font-size: 16px;color:#fff;font-weight:300;text-align:center;">$sClientEmail</p>
		</div>
		<p style="line-height: 34px;color:#5fc7ae; margin: 0 0 32px 0;"><?php esc_html_e( 'Message', 'coworking' ) ?>:</p>
		<p style="line-height: 34px;">$sClientMsg</p>
		
	</div>
</body>
</html>