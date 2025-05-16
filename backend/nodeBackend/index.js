// server.js
const express = require('express');
const http = require('http');
require('dotenv').config();
const socketIo = require('socket.io');
const cors = require('cors');
const connectDB = require('./config/db');
const RedisSubscriber = require('./redis/redis-sub');
const ChangeStreamService = require('./Services/changeStream');
const commentRoutes = require('./Routes/commentRoutes');
require('./redis/redis-pub')

class Server {
  constructor() {
    this.app = express();
    this.server = http.createServer(this.app);
    this.primaryPort = parseInt(process.env.PORT || "3001");
    this.backupPorts = [this.primaryPort + 1, this.primaryPort + 2, this.primaryPort + 3];
    this.io = socketIo(this.server, {
      cors: {
        origin: "*", 
        methods: ["GET", "POST"]
      }
    });
    
    this.setupMiddleware();
    this.setupRoutes();
    this.setupSocketIO();
    this.setupRedis();
  }

  setupMiddleware() {
    this.app.use(cors());
    this.app.use(express.json());
  }

  setupRoutes() {
    this.app.use('/api/comments', commentRoutes);
  }

  setupSocketIO() {
    this.io.on("connection", (socket) => {
      console.log(`🔌 User connected: ${socket.id}`);
    
      socket.on("join_task", (taskId) => {
        socket.join(taskId);
        console.log(`📁 Socket ${socket.id} joined task room: ${taskId}`);
        console.log(`Current rooms:`, [...socket.rooms]); 
      });

    });
  }
  async setupRedis() {
    try {
      this.redisSubscriber = new RedisSubscriber(this.io);
      // this.RedisPublisher = new RedisPublisher(this.io);
      await ChangeStreamService.watchComments();
    } catch (err) {
      console.error("❌ Failed to setup Redis:", err);
      process.exit(1);
    }
  }

  start() {
    this.tryPort(this.primaryPort);
  }

  tryPort(port) {
    this.server.on("error", (e) => {
      if (e.code === "EADDRINUSE") {
        console.error(`Port ${port} is already in use.`);
        const nextPort = this.backupPorts.shift();
        if (nextPort) {
          console.log(`Trying alternative port ${nextPort}...`);
          this.server.close();
          this.tryPort(nextPort);
        } else {
          console.error("All ports are in use. Exiting.");
          process.exit(1);
        }
      } else {
        console.error("Server error:", e);
      }
    });

    this.server.listen(port, "0.0.0.0", () => {
      console.log(`🚀 Server running on port ${port}`);
      console.log(`📡 API available at http://localhost:${port}`);
    });
  }
}

connectDB()
  .then(() => {
    const server = new Server();
    server.start();
  })
  .catch(err => {
    console.error("❌ Failed to connect to MongoDB:", err);
    process.exit(1);
  });