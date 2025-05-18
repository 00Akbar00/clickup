const express = require("express");
const http = require("http");
require("dotenv").config();
const socketIo = require("socket.io");
const cors = require("cors");
const connectDB = require("./config/db");
const RedisSubscriber = require("./redis/redis-sub");
const ChangeStreamService = require("./Services/changeStream");
const commentRoutes = require("./Routes/commentRoutes");
const notificationRoutes = require("./Routes/notificationRoutes");
require("./redis/redis-pub");

class Server {
  constructor() {
    this.app = express();
    this.server = http.createServer(this.app);
    this.primaryPort = parseInt(process.env.PORT || "3001");
    this.backupPorts = [
      this.primaryPort + 1,
      this.primaryPort + 2,
      this.primaryPort + 3,
    ];
    this.io = socketIo(this.server, {
      cors: {
        origin: "*",
        methods: ["GET", "POST"],
        credentials: true,
      },
      transports: ["websocket", "polling"],
    });

    this.setupMiddleware();
    this.setupRoutes();
    this.setupSocketIO();
    this.setupRedis();
    this.setupErrorHandling();
  }

  setupMiddleware() {
    this.app.use(
      cors({
        origin: "*",
        methods: ["GET", "POST", "PUT", "PATCH", "DELETE"],
        allowedHeaders: ["Content-Type", "Authorization"],
      })
    );
    this.app.use(express.json());
    this.app.use(express.urlencoded({ extended: true }));
  }

  setupRoutes() {
    this.app.use("/api/comments", commentRoutes);
    this.app.use("/api/notifications", notificationRoutes);

    // Health check endpoint
    this.app.get("/health", (req, res) => {
      res.status(200).json({
        status: "healthy",
        redis: this.redisSubscriber ? "connected" : "disconnected",
        mongo:
          mongoose.connection.readyState === 1 ? "connected" : "disconnected",
      });
    });
  }

  setupSocketIO() {
    this.io.on("connection", (socket) => {
      console.log(`🔌 User connected: ${socket.id}`);

      // Handle task room joining for comments
      socket.on("join_task", (taskId) => {
        socket.join(taskId);
        console.log(`📁 Socket ${socket.id} joined task room: ${taskId}`);
      });

      // Handle user room joining for notifications
      socket.on("join_user", (userId) => {
        if (userId) {
          socket.join(userId);
          console.log(`👤 Socket ${socket.id} joined user room: ${userId}`);
        }
      });

      socket.on("disconnect", () => {
        console.log(`❌ User disconnected: ${socket.id}`);
      });

      // Error handling for socket
      socket.on("error", (err) => {
        console.error(`Socket error (${socket.id}):`, err);
      });
    });

    this.io.engine.on("connection_error", (err) => {
      console.error("Socket.io connection error:", err);
    });
  }

  async setupRedis() {
    try {
      this.redisSubscriber = new RedisSubscriber(this.io);
      console.log("🟢 Redis subscriber initialized");

      // Optional: Initialize Redis publisher if needed elsewhere
      // this.redisPublisher = require('./redis/redis-pub');

      await ChangeStreamService.watchComments();
      console.log("🟢 MongoDB Change Stream watching comments");
    } catch (err) {
      console.error("❌ Failed to setup Redis:", err);
      process.exit(1);
    }
  }

  setupErrorHandling() {
    // Handle unhandled promise rejections
    process.on("unhandledRejection", (err) => {
      console.error("Unhandled Rejection:", err);
    });

    // Handle uncaught exceptions
    process.on("uncaughtException", (err) => {
      console.error("Uncaught Exception:", err);
      process.exit(1);
    });

    // Handle 404
    this.app.use((req, res, next) => {
      res.status(404).json({ error: "Not Found" });
    });

    // Handle other errors
    this.app.use((err, req, res, next) => {
      console.error("Server Error:", err.stack);
      res.status(500).json({ error: "Internal Server Error" });
    });
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
      console.log(`⚡ Socket.IO ready for connections`);

      // Optional: Log all available routes
      if (process.env.NODE_ENV === "development") {
        const routes = [];
        this.app._router.stack.forEach((middleware) => {
          if (middleware.route) {
            routes.push({
              path: middleware.route.path,
              methods: Object.keys(middleware.route.methods),
            });
          } else if (middleware.name === "router") {
            middleware.handle.stack.forEach((handler) => {
              if (handler.route) {
                routes.push({
                  path: handler.route.path,
                  methods: Object.keys(handler.route.methods),
                });
              }
            });
          }
        });
        console.log("🛣️ Available routes:", routes);
      }
    });
  }
}

// Connect to MongoDB and start server
connectDB()
  .then(() => {
    const server = new Server();
    server.start();
  })
  .catch((err) => {
    console.error("❌ Failed to connect to MongoDB:", err);
    process.exit(1);
  });
