// controllers/commentController.js
const { createComment } = require('../Services/commentService');
const Comment = require('../Models/Comment');

const createCommentHandler = async (req, res) => {
  try {
    const savedComment = await createComment(req.body);
    res.status(201).json(savedComment);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

const getCommentsForTaskHandler = async (req, res) => {
  try {
    const comments = await Comment.find({ task_id: req.params.taskId })
      .sort({ timestamp: -1 })
      .limit(50);

    res.json(comments);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
};

module.exports = {
  createCommentHandler,
  getCommentsForTaskHandler
};
