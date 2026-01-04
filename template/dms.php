<html lang="en-US">
<head>
    <title>DMS</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
    <link href="<?= assetLink('../css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= assetLink('../css/font-awesome-6.5.2.min.css') ?>" rel="stylesheet">
    <link href="<?= assetLink('../css/db.css') ?>" rel="stylesheet">
    <link rel="icon" href="<?= assetLink('../icons/jiraFetch.ico') ?>" type="image/x-icon" />

     <style>
          #version::placeholder {
            color: #aaa;
            opacity: 0.7;
          }
     </style>
</head>
<body>

<div class="container mt-2">
    <h2>DMS</h2>
    <hr>

    <form method="GET" action="?">
        <div class="row input-group">
            <div class="col-md-4">
                <div class="form">
                    <label for="version_id">ID</label>
                    <input id="version_id" class="form-control-sm w-25" name="version_id" placeholder="1" />
                </div>
            </div>

            <div class="col-md-8">
                <button type="submit" name="action[fetch]" value="1" class="btn btn-link"><i class="fas fa-database"></i> Fetch</button>
            </div>
       </div>
    </form>
</div>

</body>
</html>
