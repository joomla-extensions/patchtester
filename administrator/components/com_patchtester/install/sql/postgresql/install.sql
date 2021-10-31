CREATE TABLE IF NOT EXISTS "#__patchtester_pulls"
(
    "id"          serial                            NOT NULL,
    "pull_id"     bigint                            NOT NULL,
    "title"       character varying(200)            NOT NULL,
    "description" character varying(150) DEFAULT '' NOT NULL,
    "pull_url"    character varying(255)            NOT NULL,
    "sha"         character varying(40)  DEFAULT '' NOT NULL,
    "is_rtc"      smallint               DEFAULT 1  NOT NULL,
    "is_npm"      smallint               DEFAULT 1  NOT NULL,
    "branch"      character varying(255) DEFAULT '' NOT NULL,
    "is_draft"    smallint               DEFAULT 0  NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "#__patchtester_pulls_labels"
(
    "id"      serial                 NOT NULL,
    "pull_id" bigint                 NOT NULL,
    "name"    character varying(200) NOT NULL,
    "color"   character varying(6)   NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "#__patchtester_tests"
(
    "id"              serial                NOT NULL,
    "pull_id"         bigint                NOT NULL,
    "data"            text                  NOT NULL,
    "patched_by"      bigint                NOT NULL,
    "applied"         bigint                NOT NULL,
    "applied_version" character varying(25) NOT NULL,
    PRIMARY KEY ("id")
);
