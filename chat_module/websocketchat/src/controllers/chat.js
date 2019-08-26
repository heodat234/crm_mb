'use strict';

var q = require('q'),
	chatModel = require('../models/chat');

module.exports =  {
	/*viewTicket : async (id) => { //View a Ticket
		var d = q.defer();
		try {
			var query = await chatModel.findById(id);
			query.exec( (err, ticket) => {
				if (err) 
					d.reject(err);
				d.resolve(ticket);
			});
		} catch (err) {
			d.reject(err);
		}

		return d.promise;		
	},*/
	createMessager : async ( request ) => { //Create a Chat
		var d = q.defer();
		var data = {
			line				: request.payload.line,
			sender_id 			: request.payload.sender_id,
			sender_info 		: request.payload.sender_info,
			page_id 				: request.payload.page_id,
			text 				: request.payload.text,
			date_added 			: request.payload.updated_at || (new Date()).getTime() / 1000
		};
		try {
			var newChat = new chatModel(data);
			await newChat.save( (err, new_ticket) => {
				if (err) {
					d.reject({
						status : 0,
						message : "Add Chat error!",
						error : err
					});
				} else {
					d.resolve({
						status : 1,
						message : "Add Chat success.",
						ticket : new_ticket
					});
				}
			});
		} catch (err) {
			d.reject({
				status : 0,
				message : "Connection is incorrect!",
				error : err
			});
		}
		
		return d.promise;
	}/*,
	listAllTickets : async ( request ) => {
		let pageOptions = {
			page: (typeof request.query.page !== 'undefined')  ? parseInt(request.query.page) : 1,
			limit: (typeof request.query.limit !== 'undefined') ? parseInt(request.query.limit) : 20
		};
		//console.log(pageOptions);
		var d = q.defer();
		try {		
		 	await chatModel.find()
					.limit(pageOptions.limit)
					.skip(pageOptions.page * pageOptions.limit)
					.exec( (err, ticket)  => {
						if (err) 
							d.reject(err);
						d.resolve(ticket);
					});
		} catch (err) {
			d.reject(err);
		}		

		return d.promise;	
	},
	filterTickets : async ( request ) => {
		var d = q.defer();
		try {
			let	query = {
				assign 				: request.query.assign,
				name 				: request.query.name,
				phone 				: request.query.phone,
				email 				: request.query.email,
				description 		: request.query.description,
				description_text 	: request.query.description_text,
				custom_fields 		: request.query.custom_fields,
				group_id 			: request.query.group_id,
				company_id 			: request.query.company_id ,
				product_id 			: request.query.product_id,
				source 				: request.query.source,
				priority 			: request.query.priority,
				spam 				: request.query.spam,
				subject 			: request.query.subject,
				tags 				: request.query.tags,
				attachments 		: request.query.attachments,
				to_emails 			: request.query.to_emails,
				cc_emails 			: request.query.cc_emails,
				reply_cc_emails 	: request.query.reply_cc_emails,
				fwd_emails 			: request.query.fwd_emails,
				email_config_id 	: request.query.email_config_id,
				facebook_id 		: request.query.facebook_id,
				twitter_id 			: request.query.twitter_id,
				requester_id 		: request.query.requester_id,
				responder_id 		: request.query.responder_id,
				due_by 				: request.query.due_by,
				fr_due_by 			: request.query.fr_due_by,
				fr_escalated 		: request.query.fr_escalated,
				is_escalated 		: request.query.is_escalated,
				type 				: request.query.type,
				status 				: request.query.status,
				deleted 			: request.query.deleted,
				created_at			: request.query.created_at,
				updated_at 			: request.query.updated_at
			};

			let pageOptions = {
				page: (typeof request.query.page !== 'undefined')  ? parseInt(request.query.page) : 1,
				limit: (typeof request.query.limit !== 'undefined') ? parseInt(request.query.limit) : 20
			};

			for( var key in query ) {
				if (query[key] == "undefined" || query[key] == "" || query[key] == null){
					delete query[key];
				}
			}

			await chatModel.find(query, (err, docs) => {
				if ( err ) {
					d.reject(err);
				} else {
					d.resolve(docs);
				}
			});			
		} catch(err) {
			d.reject(err);
		}
		return d.promise;

	},
	updateTicket : async ( request ) => {
		var d = q.defer();
		var id = request.params.id;
		try {
			await chatModel.findById(id, (err, ticket) => {
				if( err ) {
					d.reject({
						status : 0,
						message : "Update Error.",
						error : err
					});
				} else {
					var	data = {
						assign 				: request.payload.assign,
						name 				: request.payload.name,
						phone 				: request.payload.phone,
						email 				: request.payload.email,
						description 		: request.payload.description,
						description_text 	: request.payload.description_text,
						custom_fields 		: request.payload.custom_fields,
						group_id 			: request.payload.group_id,
						company_id 			: request.payload.company_id ,
						product_id 			: request.payload.product_id,
						source 				: request.payload.source,
						priority 			: request.payload.priority,
						spam 				: request.payload.spam,
						subject 			: request.payload.subject,
						tags 				: request.payload.tags,
						attachments 		: request.payload.attachments,
						to_emails 			: request.payload.to_emails,
						cc_emails 			: request.payload.cc_emails,
						reply_cc_emails 	: request.payload.reply_cc_emails,
						fwd_emails 			: request.payload.fwd_emails,
						email_config_id 	: request.payload.email_config_id,
						facebook_id 		: request.payload.facebook_id,
						twitter_id 			: request.payload.twitter_id,
						requester_id 		: request.payload.requester_id,
						responder_id 		: request.payload.responder_id,
						due_by 				: request.payload.due_by,
						fr_due_by 			: request.payload.fr_due_by,
						fr_escalated 		: request.payload.fr_escalated,
						is_escalated 		: request.payload.is_escalated,
						type 				: request.payload.type,
						status 				: request.payload.status,
						deleted 			: request.payload.deleted,
						updated_at 			: request.payload.updated_at || (new Date()).getTime() / 1000
					};
	
					for( var key in data ) {
						if (data[key] == "undefined" || data[key] == "" || data[key] == null){
						 	delete data[key];
					    }
					}
					chatModel.update( {_id : id}, data, (err, affected, resp) => {
						if( err ) {
							d.reject({
								status : 0,
								message : "Lỗi Cập nhật.",
								error : err
							});
						} else {
							d.resolve({
								status : 1,
								message : "Cập nhật thành công.",
								ticket : resp
							});
						}
					} );
				}
			});
		} catch ( err ) {
			d.reject({
				status : 0,
				message : "Lỗi Cập nhật.",
				error : err
			});
		}

		return d.promise;
	},
	deleteTicket : async (request) => {
		var d = q.defer();
		try {
			var query = await chatModel.findByIdAndRemove(request.params.id, (err, ticket) => {
				if (err) 
					d.reject({
						status : 0,
						message: "Ticket successfully not deleted",
						error: err,
					});
				else {
					let response = {
						id: ticket._id,
						status : 1,
						message: "Ticket successfully deleted"			        
					};
					d.resolve(response);
				}			
			});
		} catch(err) {
			d.reject({
				status : 0,
				message: "Ticket successfully not deleted",
				error: err,
			});
		}

		return d.promise;
	},
	conversations : async ( id ) => {
		var d = q.defer();
		try {
			var query = await chatModel.findById( id );
			query.exec( (err, ticket) => {
				if (err) 
					d.reject(err);
				d.resolve(ticket);
			});	
		} catch(err) {
			d.reject(err);
		}
		
		return d.promise;	
	}*/
};