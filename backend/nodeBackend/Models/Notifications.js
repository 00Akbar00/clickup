// nodebackend/models/Notification.js
const mongoose = require("mongoose");
const Schema = mongoose.Schema;

const notificationSchema = new Schema({
  workspace_id: {
    type: String,
    required: true,
  },
  recipient_id: {
    type: String,
    required: true,
  },
  sender_id: {
    type: String,
    required: false,
  },
  type: {
    type: String,
    required: true,
    enum: [
      "task_assigned",
      "task_unassigned",
      "status_changed",
      "due_date_approaching",
      "comment_added",
      "task_updated",
      "task_completed",
      "workspace_invite",
      "role_changed",
      "file_uploaded",
      "subtask_completed",
      "time_logged",
      "task_overdue",
    ],
  },
  message: {
    type: String,
    required: true,
  },
  related_entity: {
    type: {
      type: String,
      enum: ["task", "workspace", "project", "team"],
    },
    id: String,
  },
  read: {
    type: Boolean,
    default: false,
  },
  created_at: {
    type: Date,
    default: Date.now,
  },
});

notificationSchema.index({ recipient_id: 1, read: 1 }); // For quick lookup of unread notifications

module.exports = mongoose.model("Notification", notificationSchema);
