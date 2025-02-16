<?php
$sEventTitleVar    = '$sEventTitle';
?>
<!DOCTYPE html>
<html lang="uk">
	<head>
		<link href='http://fonts.googleapis.com/css?family=Lato:300,400' rel='stylesheet' type='text/css'>
	</head>
<body style="margin: 0;padding: 0;border: 0;border-top: 8px solid #5fc7ae;border-bottom: 8px solid #5fc7ae; font-size: 100%;font: inherit;vertical-align: baseline;background:transparent;font-family: 'Lato', sans-serif; font-size: 20px;color:#7f7f7f;font-weight:300; ">
	<div class="wrapper" style="display: block;margin:0 auto;width:600px;text-align:center;padding-top: 45px;padding-bottom: 55px;">
		<h1 style="margin:0;color:#333333;font-size: 56px; font-weight:300;"><?php esc_html_e( 'Hello!', 'coworking' ) ?></h1>
		<p style="line-height: 34px;margin-bottom:50px;"><?php echo sprintf( esc_html__( 'This is copy of your event registration details %1$s %2$s', 'coworking' ), '<br>', $sEventTitleVar ) ?></p>
		<div class="eventDesc" style="width: 330px;margin: 0 auto 50px;overflow: hidden;-webkit-border-radius: 3px;-moz-border-radius: 3px;border-radius: 3px;">
			<p style="line-height: 60px;margin: 0;border-bottom: 1px solid #6dd2ba; background: #5fc7ae;font-size: 16px;color:#fff;font-weight:300;text-align:center;">$sEventDate</p>
			<p style="line-height: 60px;margin: 0;border-bottom: 1px solid #6dd2ba; background: #5fc7ae;font-size: 16px;color:#fff;font-weight:300;text-align:center;">$sEventTime</p>
			<p style="line-height: 22px;padding:8px 0; margin: 0;border-bottom: 1px solid #6dd2ba; background: #5fc7ae;font-size: 16px;color:#fff;font-weight:300;text-align:center;">$sEventLocation</p>
			<p style="line-height: 60px;margin: 0; background: #5fc7ae;font-size: 16px;color:#fff;font-weight:300;text-align:center;">$sEventPrice</p>
		</div>
		<p style="line-height: 34px;margin-bottom:0px;"><?php esc_html_e( 'Feel free to contact us anytime in case you have any questions', 'coworking' ) ?></p>
		<p style="line-height: 34px;margin-top:0px;"><?php esc_html_e( 'Contact info', 'coworking' ) ?>:</p>
		<div class="contactInfo" style="padding-top:4px; text-align:center;">
			<span style="display: inline-block;padding-right: 12px; font-size: 16px;color:#7f7f7f;"><?php esc_html_e( 'tel', 'coworking' ) ?>: $sPhone</span>
			<span style="display: inline-block;padding-left: 12px; font-size: 16px;color:#7f7f7f;"><?php esc_html_e( 'email', 'coworking' ) ?>: $sEmail</span>
		</div>
	</div>
</body>
</html>