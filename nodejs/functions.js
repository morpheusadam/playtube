var config = require('./app_start');
var connection = require('./mysql/DB');
var forEach = require('async-foreach').forEach;
var _ = require('lodash');
var decode = require('urldecode');
var read = require('read-file');
var SqlString = require('sqlstring');


// function getUserData(userID) {
//     var user = DB.query('SELECT * from users WHERE id = ? LIMIT 1', [userID]);
//     return (user[0]) ? user[0] : [];
// }

// function getUserLastMessage(userID1, userID2) {
//     var message = DB.query('SELECT * FROM messages WHERE ((from_id = ? AND to_id = ?) OR (from_id = ? AND to_id = ?)) ORDER BY id DESC LIMIT 1', [userID1, userID2, userID2, userID1]);
//     return (message) ? message : [];
// }

// function getUserList(userID1) {
//     var userList = DB.query('SELECT * FROM conversations INNER JOIN users ON conversations.user_two = users.id WHERE user_one = ? ORDER BY conversations.time DESC LIMIT 20', [userID1]);
//     return (userList) ? userList : [];
// }

// function countUnseen(userme, userhim) {
//     var query = "SELECT COUNT(*) as count FROM messages WHERE to_id = ? AND from_id = ? AND seen = 0";
//     var count_messages = DB.query(query, [userme, userhim]);
//     return (count_messages[0].count) ? count_messages[0].count : 0;
// }

// function countMessages(userme, userhim, last_id) {
//     var query = "SELECT COUNT(*) as count FROM messages WHERE ((from_id = ? AND to_id = ?) OR (from_id = ? AND to_id = ?))";
//     if (last_id > 0) {
//         query += ' AND id > ' + SqlString.escape(last_id);
//     }
//     var count_messages = DB.query(query, [userme, userhim, userhim, userme]);
//     return (count_messages[0].count) ? count_messages[0].count : '';
// }

// function getMessages(userme, userhim, last_id, count, last_message) {
//     var query = "SELECT * FROM messages WHERE ((from_id = ? AND to_id = ?) OR (from_id = ? AND to_id = ?))";
//     if (last_id > 0) {
//         query += ' AND id > ' + SqlString.escape(last_id);
//     }
//     if (count > 0) {
//         count = SqlString.escape(count);
//     }
//     if (last_message == true) {
//         query += ` ORDER BY id DESC LIMIT 1`;
//     } else {
//         query += ` ORDER BY id ASC LIMIT ${count}, 50`;
//     }
//     const get_messages = DB.query(query, [userhim, userme, userme, userhim]);
//     return (get_messages) ? get_messages : [];
// }

function getUserInfo (userID, callback) {
    var query = connection.query('SELECT * from users WHERE id = ' + connection.escape(userID));
    query.on('result', function(row) {
        callback(null, row);
    });
};

function getMedia(media) {
    return (config.amazon) ? "https://" + config.amazon_bucket + ".s3.amazonaws.com" + '/' + media : config.site_url + '/' + media;
}

function getLink(link) {
    return config.site_url + '/' + link;
}

function nl2br (str, is_xhtml) {   
    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';    
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'$2');
}

function secure(string) {
	string = nl2br(string);
	string = connection.escape(string);
	return string;
}

function getMarkeUp(string) {
    var r  = string.match(/\[a](.*?)\[\/a]/g);
    if (r) {
    	forEach(r, function (item, index, arr) {
    		item = _.replace(item, '[a]', '');
    		item = _.replace(item, '[/a]', '');
    		var url = decode(item);
    		string = _.replace(string, '[a]' + item + '[/a]', '<a href="' + decode(url) + '" target="_blank" class="hash" rel="nofollow">' + decode(url) + '</a>');
    	});
    }
    return string;
}

function editMarkup(string) {
    var r  = string.match(/\[a](.*?)\[\/a]/g);
    if (r) {
        forEach(r, function (item, index, arr) {
            item = _.replace(item, '[a]', '');
            item = _.replace(item, '[/a]', '');
            var url = decode(item);
            string = _.replace(string, '[a]' + item + '[/a]', decode(url));
        });
    }
    return string;
}

module.exports = {
	getUserInfo,
	getMedia,
	getMarkeUp,
	secure,
    getLink,
    editMarkup,
    // getUserData,
    // getUserLastMessage,
    // countMessages,
    // getMessages,
    // getUserList,
    // countUnseen
};