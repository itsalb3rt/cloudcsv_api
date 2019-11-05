CREATE TABLE cloudcsv.users (
    id_user SERIAL NOT NULL,
    email varchar(100) UNIQUE NOT NULL,
    user_name varchar(50) UNIQUE NOT NULL,
    full_name varchar(254) NOT NULL,
    password text NOT NULL,
    create_at timestamp without time zone NOT NULL,
    token text NOT NULL,
    profile_picture text,
    role varchar(5) NOT NULL,
    PRIMARY KEY (id_user)
);

ALTER TABLE cloudcsv.users
    ADD UNIQUE (email, user_name, token);


CREATE TABLE cloudcsv.table_storage (
    id_table_storage SERIAL NOT NULL,
    table_name varchar(100) UNIQUE NOT NULL,
    create_at timestamp without time zone NOT NULL,
    id_user integer NOT NULL,
    PRIMARY KEY (id_table_storage)
);

ALTER TABLE cloudcsv.table_storage
    ADD UNIQUE (table_name);

CREATE INDEX ON cloudcsv.table_storage
    (id_user);


CREATE TABLE cloudcsv.tables_columns (
    id_table_colum SERIAL NOT NULL,
    id_table_storage integer NOT NULL,
    column_name varchar(100) NOT NULL,
    type varchar(100) NOT NULL,
    length integer NOT NULL,
    PRIMARY KEY (id_table_colum)
);

CREATE INDEX ON cloudcsv.tables_columns
    (id_table_storage);


CREATE TABLE cloudcsv.notifications_emails (
    id_notification_email SERIAL NOT NULL,
    email varchar NOT NULL,
    id_table_storage integer NOT NULL,
    action varchar(8) NOT NULL,
    PRIMARY KEY (id_notification_email)
);

CREATE INDEX ON cloudcsv.notifications_emails
    (id_table_storage);


CREATE TABLE cloudcsv.delete_log (
    id_delete SERIAL NOT NULL,
    id_user integer NOT NULL,
    table_name varchar(254) NOT NULL,
    delete_at timestamp without time zone NOT NULL,
    description text NOT NULL,
    PRIMARY KEY (id_delete)
);

CREATE INDEX ON cloudcsv.delete_log
    (id_user);


CREATE TABLE cloudcsv.users_sessions (
    id_session SERIAL NOT NULL,
    id_user integer NOT NULL,
    log_at timestamp without time zone NOT NULL,
    PRIMARY KEY (id_session)
);

CREATE INDEX ON cloudcsv.users_sessions
    (id_user);

CREATE TABLE cloudcsv.recovered_accounts (
    recovered_account_id SERIAL NOT NULL,
    id_user integer UNIQUE NOT NULL,
    token varchar(200) NOT NULL,
    PRIMARY KEY (recovered_account_id)
);

CREATE INDEX ON cloudcsv.recovered_accounts
    (id_user);


ALTER TABLE cloudcsv.table_storage ADD CONSTRAINT FK_table_storage__id_user FOREIGN KEY (id_user) REFERENCES cloudcsv.users(id_user) ON DELETE CASCADE;
ALTER TABLE cloudcsv.tables_columns ADD CONSTRAINT FK_tables_columns__id_table_storage FOREIGN KEY (id_table_storage) REFERENCES cloudcsv.table_storage(id_table_storage) ON DELETE CASCADE;
ALTER TABLE cloudcsv.notifications_emails ADD CONSTRAINT FK_notifications_emails__id_table_storage FOREIGN KEY (id_table_storage) REFERENCES cloudcsv.table_storage(id_table_storage) ON DELETE CASCADE;
ALTER TABLE cloudcsv.delete_log ADD CONSTRAINT FK_delete_log__id_user FOREIGN KEY (id_user) REFERENCES cloudcsv.users(id_user);
ALTER TABLE cloudcsv.users_sessions ADD CONSTRAINT FK_users_sessions__id_user FOREIGN KEY (id_user) REFERENCES cloudcsv.users(id_user);
ALTER TABLE cloudcsv.recovered_accounts ADD CONSTRAINT FK_recovered_accounts__id_user FOREIGN KEY (id_user) REFERENCES cloudcsv.users(id_user);