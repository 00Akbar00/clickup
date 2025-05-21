
const Comment = require('../Models/Comment');

const createComment = async (commentData) => {
  const newComment = new Comment({
    task_id: commentData.task_id,
    sender_id: commentData.sender_id,
    name:commentData.name,
    comment: commentData.comment,
    files: commentData.files || []
  });

  return await newComment.save();
};

module.exports = {
  createComment
};
