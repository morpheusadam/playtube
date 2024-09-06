var fs = require('fs');
try {
	if (!config) {
		var file = fs.readFileSync(__dirname + '/config.json', 'utf8');
	    var config = JSON.parse(file);
	}
} catch (e) {
	console.log(e);
}

const db_hostname = config.db_hostname;
const db_username = config.db_username;
const db_password = config.db_password;
const db_dbname = config.db_dbname;
const amazon = config.amazon;
const amazon_bucket = config.amazon_bucket;

// server setup
const server_ip = config.server_ip;
const server_port = config.server_port;

const site_url = config.site_url; 

const ssl = config.ssl;
const ssl_privatekey_full_path = config.ssl_privatekey_full_path;
const ssl_cert_full_path = config.ssl_cert_full_path;

module.exports = {
	db_hostname,
	db_username,
	db_password,
	db_dbname,
	server_ip,
	server_port,
	site_url,
	amazon,
	amazon_bucket,
	ssl,
	ssl_privatekey_full_path,
	ssl_cert_full_path,
};