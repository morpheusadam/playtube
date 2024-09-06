var config = require('../config');
var mysql = require('mysql');

// connect to the db
dbConnectionInfo = {
  host: config.db_hostname,
  user: config.db_username,
  password: config.db_password,
  connectionLimit: 10, //mysql connection pool length
  database: config.db_dbname
};

//create mysql connection pool
var dbconnection = mysql.createPool(
  dbConnectionInfo
);

// Attempt to catch disconnects 
dbconnection.on('connection', function (connection) {
  connection.on('error', function (err) {
    console.error(new Date(), 'MySQL error', err.code);
  });
  connection.on('close', function (err) {
    console.error(new Date(), 'MySQL close', err);
  });

});

module.exports = dbconnection;