INSERT INTO configuration (configuration_title, configuration_key, configuration_value,
       configuration_description, configuration_group_id, sort_order, 
       last_modified, date_added, use_function, set_function)
       VALUES ('Keep Vistors Cart', 'KEEP_CART_ENABLED', 'True', 
			   'Keep a copy of the vistors cart in a cookie.', '15', '50',
			   NULL, now(), NULL, 'zen_cfg_select_option(array("True", "False"),');

INSERT INTO configuration (configuration_title, configuration_key, configuration_value,
       configuration_description, configuration_group_id, sort_order,
       last_modified, date_added, use_function, set_function)
	   VALUES ('Days Before Cart Expires', 'KEEP_CART_DURATION', '30',
	           'Number of days to keep visitors cart.', '15', '51',
			   NULL, now(), NULL, NULL);

INSERT INTO configuration (configuration_title, configuration_key, configuration_value,
       configuration_description, configuration_group_id, sort_order,
       last_modified, date_added, use_function, set_function)
	   VALUES ('Keep Cart Secret Key', 'KEEP_CART_SECRET', 'change me',
	           'Random characters to use as Keep Cart secret key.', '15', '52',
			   NULL, now(), NULL, NULL);
