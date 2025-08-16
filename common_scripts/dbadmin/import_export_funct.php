<?php
//==============================================================================
if (!function_exists('import_table_from_csv')) :
//==============================================================================
/*
Function import_table_from_csv

This function loads the data from a CSV file into a given DB table. There are
two alternative methods (as specified by the 'method' parameter):-

Short - This uses the MySQL LOAD DATA INFILE construct.
Long -  This performs the operation long hand to avoid having to set up any
        special MySQL privileges.
*/
//==============================================================================

function import_table_from_csv($file_path,$db,$table,$method='long')
{
    mysqli_delete_query($db,$table,'1',[]);
    if ($method == 'short') {
        $query = "LOAD DATA INFILE '$file_path' INTO TABLE $table FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\\n'";
        mysqli_query_normal($db,$query);
    }
    elseif ($method == 'long') {
        $file_contents = file($file_path);
        $field_list = $file_contents[0];
        unset($file_contents[0]);
        foreach ($file_contents as $line) {
            // Process escape sequences
            $line = stripcslashes($line);
            // Convert escape sequence for double quotes (CSV to MySQL)
            $line = str_replace('""',"\\\"",$line);
            // Add record to table
            mysqli_query_normal($db,"INSERT INTO $table ($field_list) VALUES ($line)");
        }
    }
}

//==============================================================================
/*
Function export_table_to_csv

This function dumps the data from a given DB table into a CSV file. There are
two alternative methods (as specified by the 'method' parameter):-

Short - This uses the MySQL SELECT INTO OUTFILE construct.
Long -  This performs the operation long hand to avoid having to set up any
        special MySQL privileges
*/
//==============================================================================

function export_table_to_csv($file_path,$db,$table,$fields,$method='long',$where_clause='',$order_clause='',$limit_clause='')
{
    if ($method == 'short') {
        $where_values = ['s',$file_path,'s',$table];
        $query = "SELECT * INTO OUTFILE ? FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\\n' FROM ?";
        mysqli_free_format_query($db,$query,$where_values);
    }
    elseif ($method == 'long') {
        $ofp = fopen($file_path,'w');
        if (empty($fields)) {
            // No fields are specified - indicates all fields.
            // Set the field list for queries to '*' and generate the header line.
            $field_selection = '*';
            $header_line = '';
            $field_count = 0;
            $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
            while ($row = mysqli_fetch_assoc($query_result)) {
                if ($field_count > 0) {
                    $header_line .= ',';
                }
                $header_line .= $row['Field'];
                $field_count++;
            }
            fprintf($ofp,"$header_line\n");
        }
        else {
            // Field list is provided as an array.
            // Generate the field list for queries and use the same string as the header line.
            $field_selection = '';
            $field_count = 0;
            foreach ($fields as $field_name => $field_desc) {
                if ($field_count > 0) {
                    $field_selection .= ',';
                }
                $field_selection .= $field_name;
                $field_count++;
            }
            fprintf($ofp,"$field_selection\n");
        }
        if (!empty($order_clause)) {
            $order_clause = "ORDER BY $order_clause";
        }
        if (!empty($limit_clause)) {
            $limit_clause = "LIMIT $limit_clause";
        }
    
        // Query and main loop to process the table records.
        $add_clause = '';
        $query_result = mysqli_select_query($db,$table,$field_selection,$where_clause,[],"$order_clause $limit_clause");
        while ($row = mysqli_fetch_assoc($query_result)) {
            $field_count = 0;
            foreach($row as $field) {
                // Create escape sequence for percent sign (for fprintf)
                $field = str_replace('%','%%',$field);
                // Create escape sequence for double quotes (for CSV)
                $field = str_replace('"','""',$field);
                // Create other escape sequences
                $field = addcslashes($field,"\n\r\\");
        
                if ($field_count > 0) {
                    // Not the first field so output a comma
                    fprintf($ofp,",");
                }
                // Output the field
                fprintf($ofp,"\"$field\"");
                $field_count++;
            }
            // Add line terminator
            fprintf($ofp,"\n");
        }
        fclose($ofp);
    }
}

//==============================================================================
endif;
//==============================================================================
