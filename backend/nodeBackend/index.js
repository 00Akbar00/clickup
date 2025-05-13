const express = require("express");
const bodyParser = require("body-parser");
const mongoose = require("mongoose");
const cors = require("cors");
const http = require("http");
const { Server } = require("socket.io");
const redisSub = require('./redis/redis-sub');
require("dotenv").config();

const connectDB = require("./config/db");

const app = express();
const server = http.createServer(app);

const primaryPort = parseInt(process.env.PORT || "3001");
const backupPorts = [primaryPort + 1, primaryPort + 2, primaryPort + 3];

// Middleware
app.use(cors());
app.use(bodyParser.json());

// MongoDB connection
connectDB();
redisSub();


// WebSocket setup
const io = new Server(server, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"],
  },
});


io.on("connection", (socket) => {
  console.log(`✅ User connected: ${socket.id}`);

});


// Port fallback strategy
const startServer = async () => {
  tryPort(primaryPort);

  function tryPort(port) {
    server.on("error", (e) => {
      if (e.code === "EADDRINUSE") {
        console.error(`Port ${port} is already in use.`);
        const nextPort = backupPorts.shift();
        if (nextPort) {
          console.log(`Trying alternative port ${nextPort}...`);
          server.close();
          tryPort(nextPort);
        } else {
          console.error("All ports are in use. Exiting.");
          process.exit(1);
        }
      } else {
        console.error("Server error:", e);
      }
    });

    server.listen(port, "0.0.0.0", () => {
      console.log(`🚀 Server running on port ${port}`);
      console.log(`📡 API available at http://localhost:${port}/api`);
    });
  }
};

startServer();
