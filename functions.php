<?php

// Automatic theme updates from the GitHub repository
add_filter('pre_set_site_transient_update_themes', 'automatic_GitHub_updates', 10, 1);

function automatic_GitHub_updates($data) {
    // Theme information
    $theme   = get_stylesheet(); // Folder name of the current theme
    $current = wp_get_theme()->get('Version'); // Get the version of the current theme

    // GitHub information
    $user = 'redman-creative'; // The GitHub username hosting the repository
    $repo = 'rc-fse'; // Repository name

    // Validate GitHub information
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $user) || !preg_match('/^[a-zA-Z0-9_-]+$/', $repo)) {
        return $data;
    }
    
    // Check for cached response
    $transient_key = 'github_update_' . $theme;
    $cached_response = get_transient($transient_key);
    if ($cached_response !== false) {
        return $cached_response;
    }

    // Get the latest release tag from the repository
    $response = @file_get_contents(
        'https://api.github.com/repos/'.$user.'/'.$repo.'/releases/latest',
        false,
        stream_context_create([
            'http' => [
                'header' => "User-Agent: ".$user."\r\n"
            ]
        ])
    );

    if ($response === false) {
        // Return $data unchanged if the API request fails
        return $data;
    }

    $file = json_decode($response);
    if ($file && isset($file->tag_name)) {
        
        $update = ltrim($file->tag_name, 'v'); // Remove the 'v' prefix if it exists

        // Only return a response if the new version number is higher
        if (version_compare($update, $current, '>')) {
            $package_url = $file->zipball_url;
            $data->response[$theme] = [
                'theme'       => $theme,
                'new_version' => $update,
                'url'         => $file->html_url,
                'package'     => $package_url,
            ];
            // Cache the response for 12 hours
            set_transient($transient_key, $data, 12 * HOUR_IN_SECONDS);
        }
    }

    return $data;
}