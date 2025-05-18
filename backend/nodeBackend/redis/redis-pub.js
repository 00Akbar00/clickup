const { createClient } = require("redis");
const Comment = require('../Models/Comment');
const Notification = require('../Models/Notification');

class RedisPubSub {
  constructor() {
    this.subscriber = createClient({ url: "redis://redis_server1:6379" });
    this.publisher = createClient({ url: "redis://redis_server1:6379" });
    this.initialize();
  }

  async initialize() {
    this.subscriber.on("error", (err) => console.error("❌ Redis Subscriber Error:", err));
    this.publisher.on("error", (err) => console.error("❌ Redis Publisher Error:", err));

    await this.subscriber.connect();
    await this.publisher.connect();
    console.log("🟢 Redis PubSub connected");

    await this.setupCommentSubscriptions();
    await this.setupNotificationSubscriptions();
  }

  async setupCommentSubscriptions() {
    await this.subscriber.pSubscribe('get_comments:*', async (message, channel) => {
      const taskId = channel.split(':')[1];
      try {
        const comments = await Comment.find({ task_id: taskId }).lean();
        const responseChannel = `comments_get:${taskId}`;
        await this.publisher.publish(responseChannel, JSON.stringify({ task_id: taskId, comments }));
        console.log(`📤 Sent comments for ${taskId} to ${responseChannel}`);
      } catch (err) {
        console.error("❌ Error fetching comments:", err);
      }
    });
    console.log("🟢 Subscribed to get_comments:*");
  }

  async setupNotificationSubscriptions() {
    // Subscribe to get_notifications requests
    await this.subscriber.pSubscribe('get_notifications:*', async (message, channel) => {
      const userId = channel.split(':')[1];
      try {
        const { readStatus, limit } = JSON.parse(message);
        const query = { recipient_id: userId };
        
        if (readStatus !== undefined) {
          query.read = readStatus;
        }
        
        const notifications = await Notification.find(query)
          .sort({ created_at: -1 })
          .limit(limit || 20)
          .lean();

        const responseChannel = `notifications_get:${userId}`;
        await this.publisher.publish(responseChannel, JSON.stringify(notifications));
        console.log(`📤 Sent notifications for ${userId} to ${responseChannel}`);
      } catch (err) {
        console.error("❌ Error fetching notifications:", err);
      }
    });

    // Subscribe to mark_as_read requests
    await this.subscriber.pSubscribe('mark_as_read:*', async (message, channel) => {
      const notificationId = channel.split(':')[1];
      try {
        const { userId } = JSON.parse(message);
        const updated = await Notification.findOneAndUpdate(
          { _id: notificationId, recipient_id: userId },
          { $set: { read: true } },
          { new: true }
        ).lean();

        if (updated) {
          await this.publisher.publish(
            `notification_updated:${notificationId}`,
            JSON.stringify(updated)
          );
        }
      } catch (err) {
        console.error("❌ Error marking notification as read:", err);
      }
    });

    console.log("🟢 Subscribed to notification channels");
  }

  // Method to publish notifications (can be used by other services)
  async publishNotification(notificationData) {
    try {
      await this.publisher.publish(
        'notifications',
        JSON.stringify(notificationData)
      );
      console.log('📤 Published notification to Redis');
    } catch (err) {
      console.error('❌ Error publishing notification:', err);
      throw err;
    }
  }
}

// Singleton instance
const redisPubSub = new RedisPubSub();
module.exports = redisPubSub;