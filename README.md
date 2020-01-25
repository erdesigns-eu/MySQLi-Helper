# MySQLi-Helper
Class for simple mysqli SELECT, INSERT, UPDATE and DELETE.

You can select, insert, update and delete with very simple functions. You pass the fields as an array, so if you want to select all fields you pass ['*'] , and if you want one or more fields you pass ['field1', 'field2'] (SELECT Example in image 3) . For inserting and updating it is the same, you pass an array with key - value where key is the field, and the value is the value you want to insert/update. You can pass all kinds of data in the array, non string and non integer data is serialized on insert and update - and unserialized upon selection.

Private Functions:
- clean_string (Escape string for query)
- is_serialized (Check if result string is serialized)
- is_json (Check if result string is JSON)
- insert_data_str (Convert assoc array to string for insert sql query)
- update_data_str (Convert assoc array to key = val string for update sql query)
- fields_str (Convert array to fields string)
- create_query (Create SQL Query string)


Public Functions:
- sql_insert (Insert Query)
- sql_update (Update Query)
- sql_delete (Delete Query)
- sql_search (Search by keyword)
- sql_select_array (Select query, return array)
- sql_select_array_query (Select query - custom query, return array)
- sql_backup (Backup SQL DB - return string)
- download_sql_backup (Download SQL Backup as file)
- drop_tables (Drop all tables in DB)
- sql_last_error (Get last mysqli error)

Usage:
You can pass an array or object to a column, the class will serialize all non string and non integers for insertion; and will unserialize on select. So there is no need to convert POST data before insertion or updating.

If you use it in your website, please just like our facebook page and credits would be nice :)
Please visit our website: https://erdesigns.eu and facebook page: https://fb.me/erdesigns.eu
