CREATE TABLE if not exists addressbooks (
    id SERIAL NOT NULL,
    principaluri VARCHAR(255),
    displayname VARCHAR(255),
    uri VARCHAR(200),
    description TEXT,
    synctoken INTEGER NOT NULL DEFAULT 1
);

ALTER TABLE ONLY addressbooks
    ADD CONSTRAINT addressbooks_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX if not exists addressbooks_ukey
    ON addressbooks USING btree (principaluri, uri);

CREATE TABLE if not exists cards (
    id SERIAL NOT NULL,
    addressbookid INTEGER NOT NULL,
    carddata TEXT,
    uri VARCHAR(200),
    lastmodified INTEGER,
    etag VARCHAR(32),
    size INTEGER NOT NULL
);

ALTER TABLE ONLY cards
    ADD CONSTRAINT cards_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX if not exists cards_ukey
    ON cards USING btree (addressbookid, uri);

ALTER TABLE ONLY cards
    ADD CONSTRAINT cards_addressbookid_fkey FOREIGN KEY (addressbookid) REFERENCES addressbooks(id)
        ON DELETE CASCADE;

CREATE TABLE if not exists addressbookchanges (
    id SERIAL NOT NULL,
    uri VARCHAR(200) NOT NULL,
    synctoken INTEGER NOT NULL,
    addressbookid INTEGER NOT NULL,
    operation SMALLINT NOT NULL
);

ALTER TABLE ONLY addressbookchanges
    ADD CONSTRAINT addressbookchanges_pkey PRIMARY KEY (id);

CREATE INDEX if not exists addressbookchanges_addressbookid_synctoken_ix
    ON addressbookchanges USING btree (addressbookid, synctoken);

ALTER TABLE ONLY addressbookchanges
    ADD CONSTRAINT addressbookchanges_addressbookid_fkey FOREIGN KEY (addressbookid) REFERENCES addressbooks(id)
        ON DELETE CASCADE;

CREATE TABLE if not exists calendars (
    id SERIAL NOT NULL,
    principaluri VARCHAR(100),
    displayname VARCHAR(100),
    uri VARCHAR(200),
    synctoken INTEGER NOT NULL DEFAULT 1,
    description TEXT,
    calendarorder INTEGER NOT NULL DEFAULT 0,
    calendarcolor VARCHAR(10),
    timezone TEXT,
    components VARCHAR(20),
    uid VARCHAR(200),
    transparent SMALLINT NOT NULL DEFAULT '0'
);

ALTER TABLE ONLY calendars
    ADD CONSTRAINT calendars_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX if not exists calendars_ukey
    ON calendars USING btree (principaluri, uri);

CREATE TABLE if not exists calendarobjects (
    id SERIAL NOT NULL,
    calendardata TEXT,
    uri VARCHAR(200),
    calendarid INTEGER NOT NULL,
    lastmodified INTEGER,
    etag VARCHAR(32),
    size INTEGER NOT NULL,
    componenttype VARCHAR(8),
    firstoccurence INTEGER,
    lastoccurence INTEGER,
    uid VARCHAR(200)
);

ALTER TABLE ONLY calendarobjects
    ADD CONSTRAINT calendarobjects_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX if not exists calendarobjects_ukey
    ON calendarobjects USING btree (calendarid, uri);

ALTER TABLE ONLY calendarobjects
    ADD CONSTRAINT calendarobjects_calendarid_fkey FOREIGN KEY (calendarid) REFERENCES calendars(id)
        ON DELETE CASCADE;

CREATE TABLE if not exists calendarsubscriptions (
    id SERIAL NOT NULL,
    uri VARCHAR(200) NOT NULL,
    principaluri VARCHAR(100) NOT NULL,
    source TEXT,
    displayname VARCHAR(100),
    refreshrate VARCHAR(10),
    calendarorder INTEGER NOT NULL DEFAULT 0,
    calendarcolor VARCHAR(10),
    striptodos SMALLINT NULL,
    stripalarms SMALLINT NULL,
    stripattachments SMALLINT NULL,
    lastmodified INTEGER
);

ALTER TABLE ONLY calendarsubscriptions
    ADD CONSTRAINT calendarsubscriptions_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX if not exists calendarsubscriptions_ukey
    ON calendarsubscriptions USING btree (principaluri, uri);

CREATE TABLE if not exists calendarchanges (
    id SERIAL NOT NULL,
    uri VARCHAR(200) NOT NULL,
    synctoken INTEGER NOT NULL,
    calendarid INTEGER NOT NULL,
    operation SMALLINT NOT NULL DEFAULT 0
);

ALTER TABLE ONLY calendarchanges
    ADD CONSTRAINT calendarchanges_pkey PRIMARY KEY (id);

CREATE INDEX if not exists calendarchanges_calendarid_synctoken_ix
    ON calendarchanges USING btree (calendarid, synctoken);

ALTER TABLE ONLY calendarchanges
    ADD CONSTRAINT calendarchanges_calendar_fk FOREIGN KEY (calendarid) REFERENCES calendars(id)
        ON DELETE CASCADE;

CREATE TABLE if not exists schedulingobjects (
    id SERIAL NOT NULL,
    principaluri VARCHAR(255),
    calendardata BYTEA,
    uri VARCHAR(200),
    lastmodified INTEGER,
    etag VARCHAR(32),
    size INTEGER NOT NULL
);

CREATE TABLE if not exists locks (
    id SERIAL NOT NULL,
    owner VARCHAR(100),
    timeout INTEGER,
    created INTEGER,
    token VARCHAR(100),
    scope SMALLINT,
    depth SMALLINT,
    uri TEXT
);

ALTER TABLE ONLY locks
    ADD CONSTRAINT locks_pkey PRIMARY KEY (id);

CREATE INDEX if not exists locks_token_ix
    ON locks USING btree (token);

CREATE INDEX if not exists locks_uri_ix
    ON locks USING btree (uri);

CREATE TABLE if not exists principals (
    id SERIAL NOT NULL,
    uri VARCHAR(200) NOT NULL,
    email VARCHAR(80),
    displayname VARCHAR(80)
);

ALTER TABLE ONLY principals
    ADD CONSTRAINT principals_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX if not exists principals_ukey
    ON principals USING btree (uri);

CREATE TABLE if not exists groupmembers (
    id SERIAL NOT NULL,
    principal_id INTEGER NOT NULL,
    member_id INTEGER NOT NULL
);

ALTER TABLE ONLY groupmembers
    ADD CONSTRAINT groupmembers_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX if not exists groupmembers_ukey
    ON groupmembers USING btree (principal_id, member_id);

ALTER TABLE ONLY groupmembers
    ADD CONSTRAINT groupmembers_principal_id_fkey FOREIGN KEY (principal_id) REFERENCES principals(id)
        ON DELETE CASCADE;

ALTER TABLE ONLY groupmembers
    ADD CONSTRAINT groupmembers_member_id_id_fkey FOREIGN KEY (member_id) REFERENCES principals(id)
        ON DELETE CASCADE;

CREATE TABLE if not exists propertystorage (
    id SERIAL NOT NULL,
    path VARCHAR(1024) NOT NULL,
    name VARCHAR(100) NOT NULL,
    valuetype INT,
    value TEXT
);

ALTER TABLE ONLY propertystorage
    ADD CONSTRAINT propertystorage_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX if not exists propertystorage_ukey
    ON propertystorage (path, name);

CREATE TABLE if not exists users (
    id SERIAL NOT NULL,
    username VARCHAR(50),
    digesta1 VARCHAR(32)
);

ALTER TABLE ONLY users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX if not exists users_ukey
    ON users USING btree (username);
