Here is your updated DBML file with the notifications table and related indexes removed, reflecting the fact that chat and notifications are now handled via MongoDB and not stored in the SQL database:

// ClickUp-like Project Management System (SQL Schema)

Table users {
  user_id serial [primary key]
  email varchar(255) [not null, unique]
  password_hash varchar(255) [not null]
  first_name varchar(100) [not null]
  last_name varchar(100) [not null]
  profile_picture_url varchar(255)
  created_at timestampz [default: `now()`]
  last_login timestampz
  is_active boolean [default: true]
  timezone varchar(50) [default: 'UTC']
  email_verified boolean [default: false]
}

Table workspaces {
  workspace_id serial [primary key]
  name varchar(255) [not null]
  description text
  created_by integer [ref: > users.user_id]
  created_at timestampz [default: `now()`]
  invite_token varchar(255) [unique]
  invite_token_expires_at timestampz
  logo_url varchar(255)
}

Table workspace_members {
  workspace_member_id serial [primary key]
  workspace_id integer [ref: > workspaces.workspace_id]
  user_id integer [ref: > users.user_id]
  role varchar(50) [not null, note: 'owner|admin|member|guest']
  joined_at timestampz [default: `now()`]
  indexes {
    (workspace_id, user_id) [unique]
  }
}

Table teams {
  team_id serial [primary key]
  workspace_id integer [ref: > workspaces.workspace_id]
  name varchar(255) [not null]
  description text
  is_private boolean [default: false]
  created_by integer [ref: > users.user_id]
  created_at timestampz [default: `now()`]
  color_code varchar(20)
}

Table team_members {
  team_member_id serial [primary key]
  team_id integer [ref: > teams.team_id]
  user_id integer [ref: > users.user_id]
  role varchar(50) [not null, note: 'admin|member']
  added_at timestampz [default: `now()`]
  added_by integer [ref: > users.user_id]
  indexes {
    (team_id, user_id) [unique]
  }
}

Table projects {
  project_id serial [primary key]
  team_id integer [ref: > teams.team_id]
  name varchar(255) [not null]
  description text
  created_by integer [ref: > users.user_id]
  created_at timestampz [default: `now()`]
  start_date date
  end_date date
  status varchar(50) [default: 'active', note: 'active|archived|completed']
  color_code varchar(20)
}

Table lists {
  list_id serial [primary key]
  project_id integer [ref: > projects.project_id]
  name varchar(255) [not null]
  description text
  created_by integer [ref: > users.user_id]
  created_at timestampz [default: `now()`]
  position integer [not null]
  status varchar(50) [default: 'active', note: 'active|archived']
}

Table tasks {
  task_id serial [primary key]
  list_id integer [ref: > lists.list_id]
  title varchar(255) [not null]
  description text
  created_by integer [ref: > users.user_id]
  created_at timestampz [default: `now()`]
  due_date timestampz
  priority varchar(20) [note: 'low|medium|high|urgent']
  position integer [not null]
  status varchar(50) [default: 'todo', note: 'todo|in_progress|in_review|done|blocked']
  completed_at timestampz
  time_estimate integer [note: 'in minutes']
}

Table task_assignees {
  task_assignee_id serial [primary key]
  task_id integer [ref: > tasks.task_id]
  user_id integer [ref: > users.user_id]
  assigned_by integer [ref: > users.user_id]
  assigned_at timestampz [default: `now()`]
  indexes {
    (task_id, user_id) [unique]
  }
}

Table task_comments {
  comment_id serial [primary key]
  task_id integer [ref: > tasks.task_id]
  user_id integer [ref: > users.user_id]
  content text [not null]
  created_at timestampz [default: `now()`]
  updated_at timestampz
  parent_comment_id integer [ref: > task_comments.comment_id]
}

Table attachments {
  attachment_id serial [primary key]
  task_id integer [ref: > tasks.task_id]
  uploaded_by integer [ref: > users.user_id]
  file_url varchar(255) [not null]
  file_name varchar(255) [not null]
  file_type varchar(50) [not null]
  file_size integer [not null, note: 'in bytes']
  uploaded_at timestampz [default: `now()`]
  thumbnail_url varchar(255)
}

Table activity_log {
  log_id serial [primary key]
  user_id integer [ref: > users.user_id]
  entity_type varchar(50) [not null, note: 'task|project|team|etc']
  entity_id integer [not null]
  action varchar(50) [not null, note: 'created|updated|deleted|etc']
  details jsonb
  created_at timestampz [default: `now()`]
  workspace_id integer [ref: > workspaces.workspace_id]
}

// Indexes for performance

Index idx_users_email {
  users [email]
}

Index idx_workspace_members_user {
  workspace_members [user_id]
}

Index idx_workspace_members_workspace {
  workspace_members [workspace_id]
}

Index idx_teams_workspace {
  teams [workspace_id]
}

Index idx_projects_team {
  projects [team_id]
}

Index idx_lists_project {
  lists [project_id]
}

Index idx_tasks_list {
  tasks [list_id]
}

Index idx_tasks_status {
  tasks [status]
}

Index idx_tasks_priority {
  tasks [priority]
}

Index idx_tasks_due_date {
  tasks [due_date]
}

Let me know if you'd like to add a chat_threads or messages_meta table in SQL for reference tracking or analytics (optional), or if you want the MongoDB collections to be described in DBML-like syntax too.
