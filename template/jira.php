<html lang="en-US">
<head>
    <title>Release Notes WS</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
    <link href="./../css/bootstrap.min.css" rel="stylesheet">
    <link href="./../css/font-awesome-6.5.2.min.css" rel="stylesheet">
    <link href="./../css/db.css" rel="stylesheet">
    <link rel="icon" href="./../icons/jiraFetch.ico" type="image/x-icon" />

     <style>
          #version::placeholder {
            color: #aaa;
            opacity: 0.7;
          }
     </style>
</head>
<body>

<div class="container mt-2">
    <h2>Jira Fetch</h2>
    <hr>

    <form method="POST" action="?">
        <div class="row input-group">
            <div class="col-md-4">
                <div class="form">
                    <label for="version">Release version</label>
                    <input id="version" class="form-control-sm w-25" name="version" placeholder="25.10" />
                </div>
            </div>

            <div class="col-md-4">
                <div class="form">
                    <label for="date">Date</label>
                    <input type="date" id="date" class="form-control-sm" name="date" pattern="\d{4}-\d{2}-\d{2}" />
                </div>
            </div>

            <div class="col-md-4">
                <button type="submit" name="action[fetch]" value="1" class="btn btn-link"><i class="fas fa-file-excel"></i> Download</button>
            </div>
       </div>
    </form>
</div>

<script>
document.getElementById('date').valueAsDate = new Date();
</script>

</body>
</html>
