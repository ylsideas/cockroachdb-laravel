CREATE TABLE public.migrations (
                                   id INT8 NOT NULL DEFAULT unique_rowid(),
                                   migration VARCHAR(255) NOT NULL,
                                   batch INT8 NOT NULL,
                                   CONSTRAINT migrations_pkey PRIMARY KEY (id ASC)
);
CREATE TABLE public.members (
                                id INT8 NOT NULL DEFAULT unique_rowid(),
                                name VARCHAR(255) NOT NULL,
                                email VARCHAR(255) NOT NULL,
                                password VARCHAR(255) NOT NULL,
                                remember_token VARCHAR(100) NULL,
                                created_at TIMESTAMP(0) NULL,
                                updated_at TIMESTAMP(0) NULL,
                                CONSTRAINT members_pkey PRIMARY KEY (id ASC),
                                UNIQUE INDEX members_email_unique (email ASC)
);
CREATE TABLE public.users (
                              id INT8 NOT NULL DEFAULT unique_rowid(),
                              name VARCHAR(255) NOT NULL,
                              email VARCHAR(255) NOT NULL,
                              password VARCHAR(255) NOT NULL,
                              remember_token VARCHAR(100) NULL,
                              created_at TIMESTAMP(0) NULL,
                              updated_at TIMESTAMP(0) NULL,
                              CONSTRAINT users_pkey PRIMARY KEY (id ASC),
                              UNIQUE INDEX users_email_unique (email ASC)
);
insert into "migrations" ("batch", "id", "migration") values (1, 934232578013724673, '2014_10_12_000000_create_members_table'), (1, 934232578027323393, '2014_10_12_000000_create_users_table')
