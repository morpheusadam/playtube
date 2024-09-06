// Load required libraries
var config = require('./app_start');
var express = require('express');
var app = express();
const fs = require('fs');

if (config.ssl == true) {
    const options = {
        key: fs.readFileSync(config.ssl_privatekey_full_path),
        cert: fs.readFileSync(config.ssl_cert_full_path),
    };
   var server = require('https').createServer(options, app);
} else {
   var server = require('http').createServer(app);
}

var io = require('socket.io')(server,{
    allowEIO3: true,
    cors: {
        origin: true,
        credentials: true
    },
});

var mysql = require('mysql');
var cookie = require('cookie');
var _ = require('lodash');
var forEach = require('async-foreach').forEach;
var async = require('async');
var read = require('read-file');
var unixTime = require('unix-time');
var connection = require('./mysql/DB');
var functions = require('./functions');

var cookies = [];
var socketCount = 0;
var validUsers = {};
var clients = [];


app.use(express.static(__dirname + '/node_modules'));
app.get('/', function(req, res, next) {
    res.send('Hello World! Node.js is working correctly.');
});

try {
    server.listen(config.server_port, config.server_ip);
} catch (e) {
    console.log(e);
}

io.on('connection', function(socket) {
    socketCount++;

    try {

        var cookies = cookie.parse(socket.handshake.headers.cookie);
        if (_.isString(cookies.user_id)) {
            if (!_.includes(validUsers, cookies.user_id)) {
                connection.query(`SELECT user_id FROM sessions WHERE session_id = ? LIMIT 1`, [cookies.user_id], function(error, result, field) {
                    if (error) {
                        throw error;
                    }
                    if (result.length > 0) {
                        var clinetInfo = new Object;
                        validUsers[cookies.user_id] = result[0].user_id;
                        clinetInfo.clientId = socket.id;
                        clients.push(clinetInfo);
                    }
                });
            }
        }
    } catch (e) {
        console.log(e);
    }

    if (!cookies.user_id) {
        return;
    }

    socket.on('get notifications and messages', function (data) {
        try {
             connection.query(`SELECT COUNT(*) as count FROM messages WHERE to_id = ? AND seen = 0`, [validUsers[cookies.user_id]], function (error, result, field) {
                if (error) { console.log(error); return; };
                if (result.length > 0) {
                    var messages = result[0].count;
                    connection.query(`SELECT COUNT(*) as count FROM notifications WHERE recipient_id = ? AND seen = 0`, [validUsers[cookies.user_id]], function (error, result, field) {
                        if (error) { console.log(error); return; };
                        if (result.length > 0) {
                            var notifications = result[0].count;
                        }
                        emitChanges('get notifications results', {
                            status: 200,
                            messages: messages,
                            notifications: notifications
                        });
                    });
                }
             });
        } catch (e) {

        }
    });

    socket.on('get messages', function(data) {
        try {
            functions.getUserInfo(data.id, function(error, result, field) {
                if (error) { console.log(error); return; };
                user_data = result;
                connection.query(`SELECT COUNT(*) as count FROM messages WHERE ((from_id = ? AND to_id = ? AND to_deleted = '0') OR (from_id = ? AND to_id = ? AND from_deleted = '0')) AND id > ?`, [data.id, validUsers[cookies.user_id], validUsers[cookies.user_id], data.id, data.last_id], function(error, result, field) {
                    if (error) { console.log(error); return; };
                    if (result.length > 0) {
                        let count = result[0].count - 50;
                        if (count < 1) {
                            count = 0;
                        }
                        connection.query(`SELECT * FROM messages WHERE ((from_id = ? AND to_id = ? AND to_deleted = '0') OR (from_id = ? AND to_id = ? AND from_deleted = '0')) AND id > ? ORDER BY id ASC LIMIT ${count}, 50`, [data.id, validUsers[cookies.user_id], validUsers[cookies.user_id], data.id, data.last_id], function(error, result, field) {
                            if (error) { console.log(error); return; };
                            if (result.length > 0) {
                                var html = '';
                                forEach(result, function(item, index, arr) {
                                    file = 'incoming';
                                    if (item.from_id == validUsers[cookies.user_id]) {
                                        file = 'outgoing';
                                    }
                                    message = read.sync(__dirname + '/messages/' + file + '.html', {
                                        encoding: 'utf8'
                                    });
                                    message = _.replace(message, '{{ID}}', item.id);
                                    message = _.replace(message, '{{TEXT}}', item.text);
                                    message = _.replace(message, '{{AVATAR}}', functions.getMedia(user_data.avatar));
                                    message = functions.getMarkeUp(message);
                                    html += message;
                                    if (item.seen == 0 && item.from_id != validUsers[cookies.user_id]) {
                                        time = unixTime(new Date());
                                        connection.query(`UPDATE messages SET seen = ? WHERE id = ?`, [time, item.id]);
                                    }
                                });
                                connection.query(`DELETE FROM typings WHERE user_two = ? AND user_one = ?`, [validUsers[cookies.user_id], data.id]);
                                emitChanges('get messages result', {
                                    status: 200,
                                    message: html
                                });
                            }
                        });
                    }
                });
            });
        } catch (e) {
            console.error(e);
        }
    });

    socket.on('get old messages', function(data) {
        try {

            functions.getUserInfo(data.id, function(error, result, field) {
                if (error) { console.log(error); return; };
                user_data = result;
                connection.query(`SELECT COUNT(*) as count FROM messages WHERE ((from_id = ? AND to_id = ? AND to_deleted = '0') OR (from_id = ? AND to_id = ? AND from_deleted = '0')) AND id < ?`, [data.id, validUsers[cookies.user_id], validUsers[cookies.user_id], data.id, data.first_id], function(error, result, field) {
                    if (error) { console.log(error); return; };
                    if (result.length > 0) {
                        let count = result[0].count - 50;
                        if (count < 1) {
                            count = 0;
                        }

                        connection.query(`SELECT * FROM messages WHERE ((from_id = ? AND to_id = ? AND to_deleted = '0') OR (from_id = ? AND to_id = ? AND from_deleted = '0')) AND id < ? ORDER BY id ASC LIMIT ${count}, 50`, [data.id, validUsers[cookies.user_id], validUsers[cookies.user_id], data.id, data.first_id], function(error, result, field) {
                            if (error) { console.log(error); return; };
                            if (result.length > 0) {
                                var html = '';
                                forEach(result, function(item, index, arr) {
                                    file = 'incoming';
                                    if (item.from_id == validUsers[cookies.user_id]) {
                                        file = 'outgoing';
                                    }
                                    message = read.sync(__dirname + '/messages/' + file + '.html', {
                                        encoding: 'utf8'
                                    });
                                    message = _.replace(message, '{{ID}}', item.id);
                                    message = _.replace(message, '{{TEXT}}', item.text);
                                    message = _.replace(message, '{{AVATAR}}', functions.getMedia(user_data.avatar));
                                    message = functions.getMarkeUp(message);
                                    html += message;
                                    if (item.seen == 0) {
                                        time = unixTime(new Date());
                                        connection.query(`UPDATE messages SET seen = ? WHERE id = ?`, [time, item.id]);
                                    }
                                });
                                connection.query(`DELETE FROM typings WHERE user_two = ? AND user_one = ?`, [validUsers[cookies.user_id], data.id]);
                            }
                            emitChanges('get old messages result', {
                                status: 200,
                                message: html
                            });
                        });
                    }
                });
            });
        } catch (e) {

        }
    });

    socket.on('check user list', function(data) {
        try {
            query = 'SELECT * FROM conversations INNER JOIN users ON conversations.user_two = users.id WHERE user_one = ? ORDER BY conversations.time DESC LIMIT 20';
            array = [validUsers[cookies.user_id]];
            if (data.keyword.length > 0) {
                query = `SELECT * FROM conversations INNER JOIN users ON conversations.user_two = users.id WHERE user_one = ? AND user_two IN (SELECT id FROM users WHERE username LIKE ? OR CONCAT(first_name,  ' ', last_name ) LIKE ?) ORDER BY conversations.time DESC LIMIT 20`;
                array = [validUsers[cookies.user_id], '%' + data.keyword + '%', '%' + data.keyword + '%'];
            }
            connection.query(query, array, function(error, result, field) {
                if (error) { console.log(error); return; };
                if (result.length > 0) {
                    var html = '';
                    var ids = [];
                    async.forEachOf(result, function(item, index, inner_callback) {
                        user_list = read.sync(__dirname + '/messages/user-list.html', {
                            encoding: 'utf8'
                        });
                        user_list = _.replace(user_list, '{{ID}}', item.id);
                        user_list = _.replace(user_list, '{{ID}}', item.id);
                        user_list = _.replace(user_list, '{{URL}}', functions.getLink('@' + item.username));
                        user_list = _.replace(user_list, '{{AVATAR}}', functions.getMedia(item.avatar));
                        user_list = _.replace(user_list, '{{USERNAME}}', functions.getLink('@' + item.username));
                        user_list = _.replace(user_list, '{{LAST_MESSAGE}}', '{{LAST_MESSAGE_' + item.id + '}}');
                        user_list = _.replace(user_list, '{{COUNT}}', '{{COUNT_' + item.id + '}}');
                        user_list = _.replace(user_list, '{{NAME}}', (item.first_name) ? item.first_name + ' ' + item.last_name : item.username);
                        html += user_list;
                        ids.push({
                            id: item.id
                        });
                        inner_callback(null);
                    }, function(err) {
                        if (err) {
                            console.log(err);;
                        } else {
                            forEach(ids, function(item, index) {
                                time = Number(unixTime(new Date())) - 250;
                                connection.query('SELECT COUNT(*) as count FROM typings WHERE user_two = ? AND user_one = ? AND time > ?', [validUsers[cookies.user_id], item.id, time], function(err, rows3, fields) {
                                    if (err) { console.log(err); return; };
                                    if (rows3[0].count > 0) {
                                        html = _.replace(html, '{{LAST_MESSAGE_' + item.id + '}}', '<span class="saving sidebar"><span><i class="fa fa-circle"></i></span><span><i class="fa fa-circle"></i></span><span><i class="fa fa-circle"></i></span></span>');
                                    } else {
                                        connection.query('SELECT * FROM messages WHERE ((from_id = ? AND to_id = ? AND from_deleted = 0) OR (from_id = ? AND to_id = ? AND to_deleted = 0)) ORDER BY id DESC LIMIT 1', [validUsers[cookies.user_id], item.id, item.id, validUsers[cookies.user_id]], function(err, rows, fields) {
                                            if (err) { console.log(err); return; };
                                            if (rows.length > 0) {
                                                html = _.replace(html, '{{LAST_MESSAGE_' + item.id + '}}', functions.editMarkup(rows[0].text));
                                            } else {
                                                html = _.replace(html, '{{LAST_MESSAGE_' + item.id + '}}', '');
                                            }
                                        });
                                    }
                                    connection.query('SELECT COUNT(*) as count FROM messages WHERE to_id = ? AND from_id = ? AND seen = 0', [validUsers[cookies.user_id], item.id], function(err, rows2, fields) {
                                        if (err) { console.log(err); return; };
                                        html = _.replace(html, '{{COUNT_' + item.id + '}}', (rows2[0].count == 0) ? '' : rows2[0].count);
                                    });
                                });
                            });
                        }
                    });
                }
                setTimeout(function() {
                    emitChanges('user list result', {
                        status: 200,
                        data: html
                    });
                }, 500);
            });
        } catch (e) {

        }
    });


    socket.on('send message', function(data) {
        try {
            functions.getUserInfo(data.id, function(error, result, field) {
                if (error) { console.log(error); return; };
                user_data = result;
                const privacy = JSON.parse(user_data.privacy);
                var is_sub = 0;
                if (privacy.who_can_message_me == 'subscribers') {
                    let myPromise = new Promise(function(myResolve, myReject) {
                        connection.query(`SELECT count(*) as count FROM subscriptions WHERE subscriber_id = ? AND user_id = ?`, [validUsers[cookies.user_id], data.id], function(error, result, field) {
                                is_sub = result[0].count;
                                if (is_sub == 0) {
                                    myResolve();
                                }
                                myReject();
                        }); 
                    });
                    myPromise.then(
                      function(value) {
                        emitChanges('stop_message', {
                            status: 200,
                            message_id: data.message_id
                        });
                        return false;
                      },
                      function(error) {}
                    );
                }
                if (privacy.who_can_message_me == 'no_one') {
                    emitChanges('stop_message', {
                                        status: 200,
                                        message_id: data.message_id
                                    });
                    return;
                }
                if (data.text && data.message_id) {
                    if (data.text.length > 0) {
                        urls = data.text.match(/(https?:\/\/[^\s]+)/g);
                        if (urls) {
                            forEach(urls, function(item, index, arr) {
                                data.text = _.replace(data.text, item, '[a]' + encodeURIComponent(item) + '[/a]');
                            });
                        }
                        text = functions.secure(data.text);
                        id = functions.secure(data.id);
                        time = unixTime(new Date());
                        connection.query(`SELECT count(*) as count FROM conversations WHERE user_one = ? AND user_two = ?`, [validUsers[cookies.user_id], data.id], function(error, result, field) {
                            if (error) { console.log(error); return; };
                            if (result.length > 0) {
                                count = result[0].count;
                                if (count > 0) {
                                    connection.query(`UPDATE conversations SET time = ? WHERE user_one = ? AND user_two = ?`, [time, validUsers[cookies.user_id], data.id]);
                                    connection.query(`UPDATE conversations SET time = ? WHERE user_one = ? AND user_two = ?`, [time, data.id, validUsers[cookies.user_id]]);
                                    connection.query(`SELECT count(*) as count FROM conversations WHERE user_two = ? AND user_one = ?`, [validUsers[cookies.user_id], data.id], function(error, result, field) {
                                        if (error) { console.log(error); return; };
                                        if (result.length > 0) {
                                            count = result[0].count;
                                            if (count == 0) {
                                                connection.query(`INSERT INTO conversations (user_two, user_one, time) VALUES (?, ?, ?)`, [validUsers[cookies.user_id], data.id, time]);
                                            }
                                        }
                                    });
                                } else {
                                    connection.query(`INSERT INTO conversations (user_two, user_one, time) VALUES (?, ?, ?)`, [data.id, validUsers[cookies.user_id], time]);
                                    connection.query(`SELECT count(*) as count FROM conversations WHERE user_two = ? AND user_one = ?`, [validUsers[cookies.user_id], data.id], function(error, result, field) {
                                        if (error) { console.log(error); return; };
                                        if (result.length > 0) {
                                            count = result[0].count;
                                            if (count == 0) {
                                                connection.query(`INSERT INTO conversations (user_two, user_one, time) VALUES (?, ?, ?)`, [validUsers[cookies.user_id], data.id, time]);
                                            }
                                        }
                                    });
                                }
                            }
                        });
                        connection.query(`INSERT INTO messages (from_id, to_id, text, time) VALUES('${validUsers[cookies.user_id]}', ${id}, ${text}, '${time}')`, function(error, result, field) {
                            if (error) { console.log(error); return; };
                            if (result.insertId) {
                                connection.query(`SELECT * FROM messages WHERE id = ?`, [result.insertId], function(error, result, field) {
                                    if (result.length > 0) {
                                        connection.query(`DELETE FROM typings WHERE user_one = ? AND user_two = ?`, [validUsers[cookies.user_id], data.id]);
                                        message = read.sync(__dirname + '/messages/outgoing.html', {
                                            encoding: 'utf8'
                                        });
                                        message = _.replace(message, '{{ID}}', result[0].id);
                                        message = _.replace(message, '{{TEXT}}', result[0].text);
                                        message = functions.getMarkeUp(message);

                                        emitChanges('get new message', {
                                            status: 200,
                                            message_id: data.message_id,
                                            message: message
                                        });
                                    }
                                });
                            }
                        });
                    }
                }
            });
        } catch (e) {
            console.log(e);
        }
    });

    socket.on('create typing', function(data) {
        try {
            connection.query(`SELECT COUNT(*) as count FROM typings WHERE user_one = ? AND user_two = ?`, [validUsers[cookies.user_id], data.id], function(error, result, field) {
                if (error) { console.log(error); return; };
                if (result.length > 0) {
                    count = result[0].count;
                    if (count == 0) {
                        time = unixTime(new Date());
                        connection.query(`INSERT INTO typings (user_one, user_two, time) VALUES (?, ?, ?)`, [validUsers[cookies.user_id], data.id, time]);
                    }
                }
            });
        } catch (e) {
            console.log(e);
        }
    });

    socket.on('remove typing', function(data) {
        try {
            connection.query(`SELECT COUNT(*) as count FROM typings WHERE user_one = ? AND user_two = ?`, [validUsers[cookies.user_id], data.id], function(error, result, field) {
                if (error) { console.log(error); return; };
                if (result.length > 0) {
                    count = result[0].count;
                    if (count > 0) {
                        connection.query(`DELETE FROM typings WHERE user_one = ? AND user_two = ?`, [validUsers[cookies.user_id], data.id]);
                    }
                }
            });
        } catch (e) {
            console.log(e);
        }
    });

    socket.on('check typing', function(data) {
        try {
            time = Number(unixTime(new Date())) - 250;
            connection.query(`SELECT COUNT(*) as count FROM typings WHERE user_two = ? AND user_one = ? AND time > ?`, [validUsers[cookies.user_id], data.id, time], function(error, result, field) {
                if (error) { console.log(error); return; };
                if (result.length > 0) {
                    count = result[0].count;
                    if (count > 0) {
                        emitChanges('is typing', {
                            status: 200
                        });
                    } else {
                        emitChanges('is typing', {
                            status: 400
                        });
                    }
                }
            });
        } catch (e) {
            console.log(e);
        }
    });

    socket.on('disconnect', function(data) {
        socketCount--;
        for (var i = 0, len = clients.length; i < len; ++i) {
            var c = clients[i];

            if (c.clientId == socket.id) {
                clients.splice(i, 1);
                break;
            }
        }
    });

    function emitChanges(emit, data) {
        io.to(socket.id).emit(emit, data);
    }
});