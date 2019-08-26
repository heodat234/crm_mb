'use strict';

var mongoose = require('mongoose'),
    database = require('./config').database;
    
mongoose.connect('mongodb://' + database.host + '/' + database.db);
var db = mongoose.connection;
db.on('error', console.error.bind(console, 'connection error'));
db.once('open', function callback() {
    console.log("Connection with database succeeded.");
});
exports.mongoose = mongoose;
exports.db = db;