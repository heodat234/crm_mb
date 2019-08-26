'use strict';

var db = require('../config').database;
// var database = require('../config').database;
var mongoose = require('mongoose');
var Schema = mongoose.Schema;

var fields = {
	_id	: {type: Schema.ObjectId, default : mongoose.Types.ObjectId},
	line : {type: String, default : ""},	
	sender_id : {type: String, default : ""},
	sender_info : {type: Object, default : ""},
	page_id : {type: String, default : ""},
	text : {type: String, default : ""},
	// name : {type: Object, default : ""},
	date_added : { type:Number, default: ( new Date() ).getTime() / 1000 },
};

// Define Ticket Schema
var chatSchema = new Schema(fields, {collection: "chats", versionKey: false});
//exports
module.exports = mongoose.model('chat', chatSchema);