// Routes/notificationRoutes.js
const express = require('express');
const router = express.Router();
const NotificationService = require('../Services/notificationService');

// Get all notifications for user
router.get('/notifications/:userId', async (req, res) => {
  try {
    const { read } = req.query;
    const notifications = await NotificationService.getUserNotifications(
      req.params.userId,
      read !== undefined ? read === 'true' : null
    );
    // console.log(req.params.userId,notifications);
    res.json(notifications);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Mark notification as read
router.patch('/notifications/:id/read', async (req, res) => {
  try {
    const { userId } = req.body;
    const notification = await NotificationService.markAsRead(req.params.id, userId);
    if (!notification) {
      return res.status(404).json({ error: 'Notification not found' });
    }
    res.json(notification);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Mark all notifications as read
router.patch('/notifications/read-all', async (req, res) => {
  try {
    const { userId } = req.body;
    const result = await NotificationService.markAllAsRead(userId);
    res.json({ 
      success: true,
      modifiedCount: result.modifiedCount 
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

module.exports = router;