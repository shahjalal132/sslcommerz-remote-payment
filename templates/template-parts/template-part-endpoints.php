<?php 

$base_url = site_url() . '/wp-json/api/v1';

$endpoints = [
    [
        'api_type' => 'GET',
        'path' => '/health',
        'description' => 'Apis Health Check',
    ]
];

?>

<h4 class="common-title">Endpoints</h4>

<div class="endpoints-wrapper">
    <table class="endpoints-table">
        <thead>
            <tr>
                <th>Method</th>
                <th>Endpoint</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $endpoints as $endpoint ) : ?>
            <tr>
                <td><span class="api-method <?= strtolower( $endpoint['api_type'] ) ?>"><?= $endpoint['api_type'] ?></span></td>
                <td><?= $endpoint['path'] ?></td>
                <td><?= $endpoint['description'] ?></td>
                <td>
                    <div class="button-flex">
                        <button class="copy-button">Copy</button>
                        <button class="run-button">Run</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="api-response-container" style="display: none;">
    <h4 class="common-title">API Response</h4>
    <div class="response-wrapper">
        <div class="response-status"></div>
        <div class="response-content">
            <pre class="response-json"></pre>
        </div>
    </div>
</div>