// Services/notificationService.js
const Notification = require('../Models/Notification');

class NotificationService {
  static async createNotification(notificationData) {
    try {
      const notification = new Notification(notificationData);
      await notification.save();
      return notification;
    } catch (error) {
      throw new Error(`Error creating notification: ${error.message}`);
    }
  }

  static async getUserNotifications(userId, readStatus = null) {
    try {
      const query = { recipient_id: userId };
      if (readStatus !== null) {
        query.read = readStatus;
      }
      return await Notification.find(query).sort({ created_at: -1 });
    } catch (error) {
      throw new Error(`Error fetching notifications: ${error.message}`);
    }
  }

  static async markAsRead(notificationId, userId) {
    try {
      return await Notification.findOneAndUpdate(
        { _id: notificationId, recipient_id: userId },
        { $set: { read: true } },
        { new: true }
      );
    } catch (error) {
      throw new Error(`Error marking notification as read: ${error.message}`);
    }
  }

  static async markAllAsRead(userId) {
    try {
      return await Notification.updateMany(
        { recipient_id: userId, read: false },
        { $set: { read: true } }
      );
    } catch (error) {
      throw new Error(`Error marking all notifications as read: ${error.message}`);
    }
  }
}

module.exports = NotificationService;