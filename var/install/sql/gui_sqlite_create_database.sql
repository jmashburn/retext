DROP TABLE IF EXISTS "gui_acl_privileges";
CREATE TABLE "gui_acl_privileges" (
	 "role_id" integer,
	 "resource_id" integer,
	 "allow" varchar NOT NULL
);
INSERT INTO "gui_acl_privileges" values(1,1,'post,get');

DROP TABLE IF EXISTS "gui_acl_resources";
CREATE TABLE "gui_acl_resources" (
	 "resource_id" integer PRIMARY KEY AUTOINCREMENT,
	 "resource_name" varchar
);
INSERT INTO "gui_acl_resources" values(1, 'route:Account\\Handler\\LoginHandler');

DROP TABLE IF EXISTS "gui_acl_roles";
CREATE TABLE "gui_acl_roles" (
	 "role_id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
	 "role_name" varchar NOT NULL,
	 "role_parent" integer NOT NULL
);

INSERT INTO "gui_acl_roles" VALUES (1, 'guest', '');
INSERT INTO "gui_acl_roles" VALUES (2, 'developer', 1);
INSERT INTO "gui_acl_roles" VALUES (3, 'administrator', 2);
INSERT INTO "gui_acl_roles" VALUES (4, 'root', 3);


DROP TABLE IF EXISTS "gui_available_logs";
CREATE TABLE "gui_available_logs" (
	 "id" integer PRIMARY KEY AUTOINCREMENT,
	 "name" varchar,
	 "filepath" varchar NOT NULL,
	 "directive" varchar NOT NULL,
	 "enabled" integer NOT NULL DEFAULT 1
);

DROP TABLE IF EXISTS "gui_hooks";
CREATE TABLE "gui_hooks" (
	 "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
	 "key" varchar,
	 "username" varchar,
	 "end_point" varchar,
	 "mode" varchar,
	 "creation_time" TEXT
);

DROP TABLE IF EXISTS "gui_metadata";
CREATE TABLE "gui_metadata" (
	 "id" integer PRIMARY KEY AUTOINCREMENT,
	 "name" varchar,
	 "version" varchar,
	 "data" varchar,
	 "creation_time" integer DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS "gui_snapshots";
CREATE TABLE "gui_snapshots" (
	 "id" integer PRIMARY KEY AUTOINCREMENT,
	 "name" varchar,
	 "type" integer DEFAULT 0,
	 "data" blob,
	 "creation_time" integer DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS "gui_users";
CREATE TABLE "gui_users" (
	 "id" integer PRIMARY KEY AUTOINCREMENT,
	 "key" varchar,
	 "name" varchar,
	 "password" varchar,
	 "email" varchar,
	 "role" varchar
);
INSERT INTO "gui_users" VALUES (1, 'u_9d9dxf9d9sf', 'admin', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'admin@local.dev', 'root');


DROP TABLE IF EXISTS "gui_webapi_keys";
CREATE TABLE "gui_webapi_keys" (
	 "id" integer PRIMARY KEY AUTOINCREMENT,
	 "key" varchar,
	 "username" varchar NOT NULL,
	 "name" varchar NOT NULL,
	 "hash" varchar NOT NULL,
	 "creation_time" varchar NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO "gui_webapi_keys" VALUES (1, 'wk_x8838f8x8x8', 'admin', 'apiuser', '42ba756218bc239402d402deef12d6aca30efb9708a125f245c1f752a7f2c473', '2013-08-22 19:45:43');


DROP TABLE IF EXISTS "server_event_actions";
CREATE TABLE "server_event_actions" (
	 "id" integer PRIMARY KEY AUTOINCREMENT,
	 "key" varchar,
	 "name" varchar NOT NULL,
	 "event" varchar NOT NULL,
	 "email" varchar NOT NULL,
	 "custom_action" varchar NOT NULL
);

DROP TABLE IF EXISTS "server_events";
CREATE TABLE "server_events" (
	 "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	 "key" varchar,
	 "gui_user_id" INTEGER,
	 "type" VARCHAR,
	 "name" VARCHAR,
	 "data" VARCHAR(1024,0),
	 "creation_tme" INTEGER,
	 "sent_time" INTEGER(1,0),
	 "response" TEXT
);

DROP TABLE IF EXISTS "server_notifications_actions";
CREATE TABLE server_notifications_actions (
    type INTEGER NOT NULL PRIMARY KEY,
    name VARCHAR,
    email VARCHAR,
    custom_action VARCHAR
);

DROP TABLE IF EXISTS "server_notificiations";
CREATE TABLE server_notificiations (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    type INTEGER,
    severity INTEGER,
    creation_time INTEGER,
    repeats INTEGER NOT NULL DEFAULT 0,
    show_at INTEGER NOT NULL,
    notified INTEGER NOT NULL DEFAULT 0,
    extra_data VARCHAR(1024),
    server_id INTEGER NOT NULL DEFAULT -1
);
