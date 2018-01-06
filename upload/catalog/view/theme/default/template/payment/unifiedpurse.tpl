<?php
if(!empty($Error))echo "<div>Error: $Error</div>";
elseif(isset($oncallback))
{
 echo $header; ?><?php echo $column_left; ?><?php echo $column_right; ?>
<div id="content" class='container'>
	<?php 
	//echo $content_top; 
	?>
	
	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb) { ?>
			<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php } ?>
	</div>
	<?php echo $toecho; ?>
	<?php echo $content_bottom; ?>
</div>
<?php echo $footer; 

}
else
{
if(!empty($transaction_history_link))
{
?>
 <div>
 Copy this url to access the Transaction History.<br/>
 <a href='<?php echo $transaction_history_link; ?>'><?php echo $transaction_history_link;?></a>
 </div>
 <?php
 }
 ?>
<form action="<?php echo $action; ?>" method="post">	
	<input type='hidden' name='amount' value='<?php echo $ap_amount; ?>' />
	<input type='hidden' name='receiver' value='<?php echo $ap_merchant;?>' />
	<input type='hidden' name='currency' value='<?php echo $ap_currency;?>' />
	<input type='hidden' name='email' value='<?php echo $ap_email;?>' />
	<input type='hidden' name='ref' value='<?php echo $trans_id; ?>' />
	<input type='hidden' name='memo' value="<?php echo $ap_itemname ?>" />
	<input type='hidden' name='notification_url' value='<?php echo $notify_url;?>' />
	<input type='hidden' name='success_url' value='<?php echo $ap_cancelurl; ?>' />
	<input type='hidden' name='cancel_url' value='<?php echo $ap_returnurl; ?>' />

	<b> Note : </b> If you Choose Payment Gateway as UnifiedPurse, then all other currency format converted into Naira (NGN / N) at the time of Payment!
  <div class="buttons">
    <div class="pull-right">
      <input type="submit" value="<?php echo $button_confirm; ?>" class="btn btn-primary" />
    </div>
  </div>
</form>
<?php
}
?>
