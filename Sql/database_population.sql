create database if not exists shopping_api;

use shopping_api;

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL UNIQUE,
  `password` varchar(64) NOT NULL,
  `api_key_id` varchar(32) NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `salt` varchar(32) NOT NULL,	
  PRIMARY KEY (`user_id`)
);

CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(32) NOT NULL,
  `price` decimal(10,0) NOT NULL,
  `inventory_count` int(11) NOT NULL,
  PRIMARY KEY (`product_id`)
);

CREATE TABLE IF NOT EXISTS `carts` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `items_list` text,
  `total_price` decimal(10,0),
  `cart_status` varchar(32) NOT NULL,			
  `user_id` int(11) NOT NULL,
   PRIMARY KEY (`cart_id`),
   FOREIGN KEY (`user_id`) REFERENCES users(`user_id`)	
);

INSERT INTO `users` (`username`,`password`, `api_key_id`,`api_key`,`salt`) VALUES
('admin','$2a$10$somerandomsaltforadmieqrSjdBii8c4CK1c5tw05aQyqIMnj3Lu','adminKey','$2a$10$somerandomsaltforadmieeCuaDqfK5Yq5feKLLYxVBArBql54Psm','somerandomsaltforadmin'),
('john','$2a$10$donothavesaltlikethisuxLjbaJtiwLBMhuwCJSnI.hzCtxyyay6','userLock','$2a$10$donothavesaltlikethisuZ8xoUabicq3CUQOFUVZG.28IwqPMqXi','donothavesaltlikethisy');

INSERT INTO `products` (`title`,`price`, `inventory_count`) VALUES
('LG P880 4X HD', '600',5),
('Google Nexus 4', '1000',3),
('Samsung Galaxy S4', '600', 0),
('Bench Shirt','150', 10),
('Lenovo Laptop','3990', 2),
('Rolex Watch', '25000', 7),
('iphone 6s', '25000', 4),
('sony bravia', '1500', 0),
('philips trimmer', '100', 20),
('Omega Watch', '20000', 6),
('Samsung Galaxy S9', '1200', 1);







