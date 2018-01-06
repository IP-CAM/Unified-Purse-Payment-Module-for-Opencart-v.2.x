CREATE TABLE IF NOT EXISTS oc_unifiedpurse(
				id int not null auto_increment,
				primary key(id),
				order_id INT NOT NULL,unique(order_id),
				date_time datetime,
				transaction_id INT(48),
				approved_amount DOUBLE NOT NULL,
				customer_email VARCHAR(128),
				response_description VARCHAR(225),
				response_code VARCHAR(5),
				transaction_amount DOUBLE NOT NULL,
				customer_id INT NOT NULL
				)"