DROP TABLE IF EXISTS "retext_codes";
CREATE TABLE "retext_codes" (
 "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
 "key" varchar NOT NULL,
 "code" varchar,
 "message" varchar,
 "mode" varchar,
 "creation_time" TEXT
);

DROP TABLE IF EXISTS "retext_messages";
CREATE TABLE "retext_messages" (
 "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
 "key" varchar NOT NULL,
 "code" VARCHAR NOT NULL,
 "message_received" blob,
 "message_sent" blob,
 "status" text,
 "creation_time" text
);