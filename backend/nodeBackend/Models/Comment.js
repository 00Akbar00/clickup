// models/Comment.js
const mongoose = require('mongoose');
const Schema = mongoose.Schema;

const fileSchema = new Schema({
  filename: String,
  originalname: String,
  mimetype: String,
  size: Number,
  path: String,
  uploadedAt: {
    type: Date,
    default: Date.now
  }
});

const commentSchema = new Schema({
  task_id: {
    type: String,
    required: true
  },
  sender_id: {
    type: String,
    required: true
  },
  comment: {
    type: String,
    required: false
  },
  files: [fileSchema],
  fromRedis: {  
    type: Boolean,
    default: false
  },
  timestamp: {
    type: Date,
    default: Date.now
  }
});

commentSchema.index({ task_id: 1 }); // Index for faster querying by task_id

module.exports = mongoose.model('Comment', commentSchema);