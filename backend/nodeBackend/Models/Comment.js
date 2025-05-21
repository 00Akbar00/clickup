
const mongoose = require('mongoose');
const Schema = mongoose.Schema;
const fileSchema = require('../Models/File'); 

const commentSchema = new Schema({
  task_id: {
    type: String,
    required: true
  },
  sender_id: {
    type: String,
    required: true
  },
  name:{
    type:String,
    required:true,
  },
  comment: {
    type: String,
    required: false
  },
  files: [fileSchema],
  timestamp: {
    type: Date,
    default: Date.now
  }
});

commentSchema.index({ task_id: 1 }); // Index for faster querying by task_id

module.exports = mongoose.model('Comment', commentSchema);