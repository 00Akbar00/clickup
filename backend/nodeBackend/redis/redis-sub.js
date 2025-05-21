// redis/redis-sub.js
const { createClient } = require("redis");
const Notification = require("../Models/Notifications");
const { createComment } = require('../Services/commentService');

class RedisSubscriber {
  constructor(io) {
    this.io = io;
    this.redisClient = createClient({ url: "redis://redis_server1:6379" });
    this.setupSubscriber();
  }

  async setupSubscriber() {
    this.redisClient.on("error", (err) =>
      console.error("❌ Redis Subscriber Error:", err)
    );

    await this.redisClient.connect();
    console.log("🟢 Redis Subscriber connected");

    // Subscribe to both comments and notifications channels
    await Promise.all([
      this.subscribeToComments(),
      this.subscribeToNotifications(),
    ]);
  }

  async subscribeToComments() {
    await this.redisClient.subscribe("comments", async (message) => {
      try {
        const commentData = JSON.parse(message);
        
        this.io.to(commentData.task_id).emit("new_comment", commentData);
        await createComment({
          ...commentData,
          publishedAt: new Date(),
        });
      } catch (err) {
        console.error("Error processing comment message:", err);
      }
    });
  }

  async subscribeToNotifications() {
    await this.redisClient.subscribe("notifications", async (message) => {
      try {
        const notificationData = JSON.parse(message);
        console.log("📨 Received notification:", notificationData);

        // Save to MongoDB
        const notification = new Notification(notificationData);
        await notification.save();

        // Emit to specific user's room
        this.io
          .to(notificationData.recipient_id)
          .emit("new_notification", notification);
        console.log(
          `📢 Notification sent to user ${notificationData.recipient_id}`
        );
      } catch (err) {
        console.error("❌ Error processing notification:", err);
      }
    });
  }
}

module.exports = RedisSubscriber;
