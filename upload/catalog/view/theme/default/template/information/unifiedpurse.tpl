<?php echo $header; ?><?php echo $column_left; ?><?php echo $column_right; ?>
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
<?php echo $footer; ?>