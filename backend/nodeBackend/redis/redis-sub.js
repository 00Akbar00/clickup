const Redis = require('ioredis');

const redis = new Redis({
  host: 'redis_server',  
  port: 6379,
  db: 0,
});

function redisSub() {
    redis.subscribe('', () => {
        console.log('🟢 Subscribed to Redis channel');
      });

}

module.exports = redisSub;