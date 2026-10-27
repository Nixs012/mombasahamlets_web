-- =====================================================================
-- Migration: Add team logo columns to matches table
-- Date: 2026-10-27
-- Description: Adds home_team_logo and away_team_logo columns to store
--              uploaded team logos for each match. These columns are
--              nullable and default to NULL so existing matches are
--              unaffected.
-- =====================================================================

-- Add home_team_logo column (nullable, no default to avoid altering existing rows)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'matches'
                   AND COLUMN_NAME = 'home_team_logo');

SET @sql_add_home_logo = IF(@col_exists > 0,
    'SELECT 1',
    'ALTER TABLE matches ADD COLUMN home_team_logo VARCHAR(255) DEFAULT NULL AFTER away_team'
);

PREPARE stmt1 FROM @sql_add_home_logo;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

-- Add away_team_logo column (nullable, no default to avoid altering existing rows)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'matches'
                   AND COLUMN_NAME = 'away_team_logo');

SET @sql_add_away_logo = IF(@col_exists > 0,
    'SELECT 1',
    'ALTER TABLE matches ADD COLUMN away_team_logo VARCHAR(255) DEFAULT NULL AFTER home_team_logo'
);

PREPARE stmt2 FROM @sql_add_away_logo;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Create uploads directory for match team logos (via SQL comment; directory created in PHP)
-- Physical path: frontend/images/match-logos/
