-- Add 'Other' entry to client table with client_id = '-1'
-- This serves as a catch-all option in the Project/Client dropdown

INSERT INTO `client` (`client_id`, `client_name`, `email`, `is_active`)
VALUES ('-1', 'Other', '', 1);
