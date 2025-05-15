const express = require('express');
const router = express.Router();
const commentController = require('../Controllers/commentController');

// Create a new comment
router.post('/', commentController.createCommentHandler);

// Get comments for a specific task
router.get('/:taskId', commentController.getCommentsForTaskHandler);

module.exports = router;
