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
        // console.log(commentData);
        // // Add origin flag before saving
        const newComment = await createComment({
          ...commentData,
          publishedAt: new Date() // Add timestamp
        });
        
        this.io.to(commentData.task_id).emit('new_comment', newComment);
      } catch (err) {
        console.error("Error processing message:", err);
      }
    });
  }
}

module.exports = RedisSubscriber;


// const { createClient } = require("redis");
// const Comment = require('../Models/Comment');

// class RedisSubscriber {
//   constructor(io) {
//     this.io = io;
//     this.redisClient = createClient({ url: "redis://redis_server1:6379" });
//     this.setupSubscriber();
//   }

//   async setupSubscriber() {
//     this.redisClient.on("error", (err) => 
//       console.error("❌ Redis Subscriber Error:", err));

//     await this.redisClient.connect();
//     console.log("🟢 Redis Subscriber connected");

//     await this.redisClient.subscribe('comments', async (message) => {
//       try {
//         const commentData = JSON.parse(message);
//         console.log('📨 Received comment from Redis:', commentData);
        
//         // Emit to all clients in the task room
//         this.io.to(commentData.task_id).emit('new_comment', commentData);
//         console.log(`📢 Emitted to Socket.IO room ${commentData.task_id}`);
        
//       } catch (err) {
//         console.error("❌ Error processing Redis message:", err);
//       }
//     });
//   }
// }

// module.exports = RedisSubscriber;