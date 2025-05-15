const { createClient } = require("redis");
const { createComment } = require('../Services/commentService');

class RedisSubscriber {
  constructor(io) {
    this.io = io;
    this.redisClient = createClient({ url: "redis://redis_server1:6379" });
    this.setupSubscriber();
  }

  async setupSubscriber() {
    this.redisClient.on("error", (err) => 
      console.error("❌ Redis Subscriber Error:", err));

    await this.redisClient.connect();
    console.log("🟢 Redis Subscriber connected");

    // Listen for Laravel Redis messages
    await this.redisClient.subscribe('comments', async (message) => {
      try {
        const commentData = JSON.parse(message);
        console.log('📨 Received comment from Laravel:', commentData.task_id);

        const newComment = await createComment({
          ...commentData,
          fromRedis: true
        });
        
        // Broadcast to Socket.IO room
        this.io.to(commentData.task_id).emit('new_comment', newComment);
      } catch (err) {
        console.error("❌ Error processing Redis message:", err);
      }
    });
  }
}

module.exports = RedisSubscriber;