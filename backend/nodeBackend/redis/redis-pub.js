const { createClient } = require("redis");
const Comment = require('../Models/Comment');

(async () => {
  const subscriber = createClient({ url: "redis://redis_server1:6379" });
  const publisher = createClient({ url: "redis://redis_server1:6379" });

  subscriber.on("error", (err) => console.error("❌ Redis Subscriber Error:", err));
  publisher.on("error", (err) => console.error("❌ Redis Publisher Error:", err));

  await subscriber.connect();
  await publisher.connect();

  await subscriber.pSubscribe('get_comments:*', async (message, channel) => {
    const taskId = channel.split(':')[1];

    try {
      const comments = await Comment.find({ task_id: taskId }).lean();
      const responseChannel = `comments_get:${taskId}`;

      await publisher.publish(responseChannel, JSON.stringify({ task_id: taskId, comments }));
      console.log(`📤 Sent comments for ${taskId} to ${responseChannel}`);
    } catch (err) {
      console.error("❌ Error fetching comments:", err);
    }
  });

  console.log("🟢 Redis Node subscriber for get_comments:* started");
})();
