// Services/changeStream.js
const { createClient } = require("redis");
const Comment = require('../Models/Comment');

class ChangeStreamService {
  constructor() {
    this.redisPublisher = createClient({ url: "redis://redis_server1:6379" });
    this.setupPublisher();
  }

  async setupPublisher() {
    this.redisPublisher.on("error", (err) => 
      console.error("❌ Redis Publisher Error:", err));
    
    await this.redisPublisher.connect();
    console.log("🟢 Redis Publisher connected");
  }

  async watchComments() {
    const changeStream = Comment.watch();

    changeStream.on("change", async (change) => {
      if (change.operationType === "insert") {
        const comment = change.fullDocument;
        const channel = `comments:${comment.task_id}`;

        try {
          await this.redisPublisher.publish(channel, JSON.stringify(comment));
          console.log(`📤 Published comment to ${channel}`);
        } catch (err) {
          console.error("❌ Failed to publish comment:", err);
        }
      }
    });

    changeStream.on("error", (error) => {
      console.error("❌ Change Stream error:", error);

    });
  }
}

module.exports = new ChangeStreamService();