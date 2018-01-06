<?php
###############################################################################
# PROGRAM     : UnifiedPurse OpenCart 2.00  Payment Module                           #
# DATE	      : 09-06-2015                       				              #
# AUTHOR      : UNIFIEDPURSE                                                #
# AUTHOR URI  : https://unifiedpurse.com	                                      #
###############################################################################
class ControllerPaymentUnifiedPurse extends Controller 
{
	public function index()
	{
		$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');
		$data['order_id'] = $order_id =  $this->session->data['order_id'];
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$data['action'] = 'https://unifiedpurse.com/sci';

		$data['ap_merchant'] = $this->config->get('unifiedpurse_merchant_id');
		$data['ap_amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		$data['ap_currency'] = $order_info['currency_code'];
		$data['ap_email'] = $order_info['email'];
		$data['ap_purchasetype'] = 'Item';
		$data['ap_itemname'] = $this->config->get('config_name') . ' - #' . $this->session->data['order_id'];
		$data['ap_itemcode'] = $this->session->data['order_id'];
		$data['ap_returnurl'] = $this->url->link('checkout/success');
		$data['ap_cancelurl'] = $this->url->link('checkout/checkout', '', 'SSL');	
		$data['notify_url'] = $this->url->link('payment/unifiedpurse/callback', '', 'SSL');

		$data['unifiedpurse_currency_code']=($order_info['currency_code']=='USD')?840:566;
		$data['trans_id'] = $trans_id =  time(); // date("ymds");
		$data['full_name'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8')  . ' ' . html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');		
		
		$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('unifiedpurse_pending_status_id'));
		if ($this->customer->isLogged())
		{
			$data['transaction_history_link']=$this->url->link('information/unifiedpurse');
		}
	//CUSTOM DATABASE LOGGIN
		$sql="CREATE TABLE IF NOT EXISTS ".DB_PREFIX."unifiedpurse(
				id INT NOT NULL AUTO_INCREMENT,PRIMARY KEY(id),
				order_id INT NOT NULL,unique(order_id),
				date_time DATETIME DEFAULT '1970-01-01 00:00:00',
				transaction_id INT NOT NULL DEFAULT 0,
				approved_amount DOUBLE NOT NULL DEFAULT 0,
				currency VARCHAR(3) NOT NULL DEFAULT '',
				customer_email VARCHAR(128) NOT NULL DEFAULT '',
				response_description VARCHAR(225) DEFAULT '',
				response_code VARCHAR(5) NOT NULL DEFAULT '',
				transaction_amount DOUBLE NOT NULL DEFAULT 0,
				customer_id INT NOT NULL DEFAULT 0
				)";
		$this->db->query($sql);
		$customer_id=$this->customer->isLogged()?$this->customer->getId():"";
		
		$this->db->query("INSERT INTO ".DB_PREFIX."unifiedpurse
		(order_id,transaction_id,date_time,transaction_amount,currency,
		customer_email,customer_id) 
		VALUES
		('$order_id','$trans_id',NOW(),'{$data['ap_amount']}','{$data['ap_currency']}',
		'".$this->db->escape($order_info['email'])."','$customer_id')");
		
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/unifiedpurse.tpl')) {
			return $this->load->view($this->config->get('config_template') . '/template/payment/unifiedpurse.tpl', $data);
		} else {
			return $this->load->view('default/template/payment/unifiedpurse.tpl', $data);
		}
	}
	
	function notifyAdmin($title="")
	{
		if(!$this->config->get('unifiedpurse_debug'))return;
		$post_data=json_encode($this->request->post);
		$msg="$title<br/>Post Data: $post_data";
		$this->log->write('UnifiedPurse :: Debug Info' . $msg);
	}
	
	
	public function callback() 
	{	
		$trans_ref = @$this->request->post['ref'];
		$order_info=array();
		$order_id="";
	
		if(!empty($trans_ref))$query=$this->db->query("SELECT * FROM ".DB_PREFIX."unifiedpurse WHERE transaction_id='".$this->db->escape($trans_ref)."' LIMIT 1");
		
		if(empty($trans_ref))$toecho="<h3>Transaction reference not supplied!</h3>";
		if(empty($query->row))$toecho="<h3>Transaction record #$trans_ref not found!</h3>";
		elseif(!empty($query->row['response_code']))$toecho="<h3>Transaction Ref $trans_ref has been already processed!</h3>";		
		else
		{
			$order_id=$query->row['order_id'];
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($order_id);
			$order_status_id = $this->config->get('unifiedpurse_failed_status_id');	
			$ap_amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
			
				if(empty($order_info))
				{
					$info="Order info not found";
					$this->notifyAdmin($info);
				}
				else
				{
					$mertid=$this->config->get('unifiedpurse_merchant_id');
					$amount=$query->row['transaction_amount'];
					$unifiedpurse_tranx_id=$query->row['transaction_id'];
					$currency=$query->row['currency'];
					$temp_amount=floatval($ap_amount);
					
					$url="https://unifiedpurse.com/api_v1?action=get_transaction&receiver=$mertid&ref=$unifiedpurse_tranx_id&amount=$temp_amount&currency=$currency";
					$ch = curl_init();
					//	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);			
					curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_URL, $url);
					
					$response = curl_exec($ch);
					$returnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					curl_close($ch);
					
					if($returnCode == 200)
					{
						$json=@json_decode($response,true);
					}
					else
					{
						$success=false;
						$json=null;
						$info="Error ($returnCode) accessing unifiedpurse confirmation page";
						//$this->notifyAdmin($info);
						//$order_status_id = $this->config->get('unifiedpurse_pending_order_status_id');
					}
					
					
					if(!empty($json))
					{
						if($json['status_msg']=='COMPLETED')
						{
							$order_status_id = $this->config->get('unifiedpurse_completed_status_id');
							$info="Payment Confirmation Successfull";
							$success=true;
						}
						else//transaction not completed for one reason or the other.
						{
							if($json['status_msg']=='FAILED')$order_status_id = $this->config->get('unifiedpurse_failed_status_id');	
							else $order_status_id = $this->config->get('unifiedpurse_pending_order_status_id');	
							$info="Payment Not Cofirmed: ".$json['info'];
						}
						
						
						//$this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
						
						if(!$order_info['order_status_id'])$this->model_checkout_order->confirm($order_id, $order_status_id);
						else $this->model_checkout_order->update($order_id, $order_status_id);		
						

						$this->db->query("UPDATE ".DB_PREFIX."unifiedpurse SET
							approved_amount='".$this->db->escape($json['amount'])."',
							response_code='{$json['status']}',
							response_description='".$this->db->escape($json['info'])."'
							WHERE order_id='$order_id' LIMIT 1");
					}
					
					$this->notifyAdmin("$info , Response: $response");
				}			
			}

       $this->document->setTitle("UnifiedPurse Order Payment: $info");
		

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');

		$data['breadcrumbs'] = array(); 
		$data['breadcrumbs'][] = array(
			'text'			=> $this->language->get('text_home'),
			'href'			=> $this->url->link('common/home'),           
			'separator'		=> false
		);
		$data['breadcrumbs'][] = array(
			'text'			=> "UnifiedPurse Payment Callback",
			'href'      	=> "",
			'separator' 	=> '/'
		);   
      
		  
		$toecho= "
					<style type='text/css'>
					.errorMessage,.successMsg
					{
						color:#ffffff;
						font-size:18px;
						font-family:helvetica;
						border-radius:9px;
						display:inline-block;
						max-width:350px;
						border-radius: 8px;
						padding: 4px;
						margin:auto;
					}
					
					.errorMessage{background-color:#ff3300;}
					
					.successMsg{background-color:#00aa99;}
					
					body,html{min-width:100%;}
				</style>
				";
		$home_url=$this->url->link("common/home",'', 'SSL');
		if(!empty($success))
		{
		
			$toecho.="<div class='successMsg'>
					$info<br/>
					Your order has been successfully Processed <br/>
					ORDER ID: $order_id<br/>
					<a href='$home_url' style='color:#fff;'>CLICK TO RETURN HOME</a></div>";
		}
		else
		{
			$toecho.="<div class='errorMessage'>
					Your transaction was not successful<br/>
					REASON: $info<br/>
					ORDER ID: $order_id<br/>
					<a href='$home_url' >CLICK TO RETURN HOME</a></div>";
		}
		
		$data['oncallback']=true;
		$data['toecho']=$toecho;
		
		$this->response->setOutput($this->load->view('default/template/payment/unifiedpurse.tpl', $data));		
	}
}