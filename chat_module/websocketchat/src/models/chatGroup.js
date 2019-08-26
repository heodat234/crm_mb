'use strict';

// var db = require('../config').database;
var db = require('../database').db;
// var database = require('../config').database;
var mongoose = require('mongoose');
var Schema = mongoose.Schema;

var fields = {
	_id	: {type: Schema.ObjectId, default : mongoose.Types.ObjectId},
	user_id_create : {type: String, default : ""},	
	type : {type: String, default : ""},
	from : {type: Object, default : ""},
	to : {type: Object, default : ""},
	page_id : {type: String, default : ""},
	date_active : {type: Number, default : ""},
	date_added : { type:Number, default: ( new Date() ).getTime() / 1000 },
};

// Define Ticket Schema
var nodejsGroupSchema = new Schema(fields, {collection: "chatnodejsGroup", versionKey: false});
//exports
module.exports = mongoose.model('chatnodejsGroup', nodejsGroupSchema);