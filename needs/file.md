Google Link
https://docs.google.com/document/d/1BFduQy4cMzJJNzLIbUNadcP5piajizxeW8BvTXbDLjU/edit?usp=sharing

DATABASE SCHEMA UPDATED

Table users {
  user_id uuid [primary key]
  full_name varchar(255) [not null]
  email varchar(255) [not null, unique]
  password varchar(255) [not null]
  profile_picture_url varchar(255)
  email_verified_at timestampz
  reset_token varchar(255)
  reset_token_expires_at timestampz
  created_at timestampz [default: `now()`]
  updated_at timestampz [default: `now()`]
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

Table workspace_invitations {
  invitation_id varchar [primary key]
  workspace_id integer [ref: > workspaces.workspace_id]
  email varchar(255) [not null]
  role varchar(50) [not null, note: 'owner|admin|member|guest']
  inviter_name varchar(255)
  invite_token varchar(255)
  expires_at timestampz
  status varchar(50)
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
  workspace_member_id integer [ref: > workspace_members.workspace_member_id]
  assigned_by integer [ref: > users.user_id]
  assigned_at timestampz [default: `now()`]
  indexes {
    (task_id, workspace_member_id) [unique]
  }
}