<?php /** @var $dbOpts array */ ?>
<?php /** @var $form string */ ?>
<?php /** @var $currentComparisonState array */ ?>
<html lang="en-US">
<head>
    <title>OSIV-DB Web Interface</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
    <link href="./../css/bootstrap.min.css" rel="stylesheet">
    <link href="./../css/db.css" rel="stylesheet">
    <link href="./../css/font-awesome-6.5.2.min.css" rel="stylesheet">
    <link rel="icon" href="./../icons/huzki.ico" type="image/x-icon" />
</head>
<body>

<div class="container-fluid mt-2">
    <form method="POST" action="?" target="_blank">
        <div class="row input-group">
            <div class="col-md-3 align-self-center">
                <label for="db_name" class="form-label h2">
                    OSIV-DB Web Interface
                </label>
            </div>
            <div class="col-auto">
                <div class="d-flex align-items-center gap-2">
                    <label for="db_name">Database</label>
                    <select class="form-select" id="db_name" name="db">
                        <?php foreach ($dbOpts as $dbVal => $dbOpt) { ?>
                            <option value="<?php echo $dbVal; ?>"><?php echo $dbOpt['displayName']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="ms-md-auto col-md-4 text-end opacity-25<?php echo $debug ? '' : ' d-none' ?>">
                <input class="form-check-input" type="checkbox" value="1" id="db_debug" name="debug">
                <label class="form-check-label small" for="db_debug">
                    debug mode
                </label>
            </div>
        </div>

        <!-- Nav tabs -->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link<?php echo empty($form) || $form === 'sql' ? ' active' : '' ?>" data-bs-toggle="tab"
                   href="#sql">SQL</a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?php echo $form === 'compare' ? ' active' : '' ?>" data-bs-toggle="tab"
                   href="#compare">Comparison</a>
            </li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content">
            <div class="tab-pane container-fluid<?php echo empty($form) || $form === 'sql' ? ' active' : ' fade' ?>" id="sql">
                <div class="row">
                    <div class="col-md-8 mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="sortFields" name="sortFields">
                            <label class="form-check-label" for="sortFields">
                                Sort fields A-Z
                            </label>
                        </div>

                        <textarea id="ta_q" name="q" rows="5" placeholder="SQL query" class="form-control mt-3"></textarea>

                        <div class="row mt-4">
                            <div>
                                <button type="button" id="btn_execute" class="btn btn-primary">Execute and View</button>
                                <button type="submit" name="action[query]" value="1" class="btn btn-primary">Execute</button>
                            </div>
                        </div>

                        <div id="query_result" class="card card-body mt-2" style="max-height: 500px; overflow-y: auto;">
                        </div>

                        <h4 class="mt-4">Query templates</h4>
                        <div class="card card-body">
                            <div><code>select top 10 * from pub.sendung</code></div>
                            <div class="mt-3"><code>select * from pub.entscheid where entscheid_id=23956</code></div>
                            <div class="mt-3"><code>select * from pub.sendung where sendung_id=87163</code></div>
                            <div class="mt-3"><code>select * from pub.stamm where stamm_id=8070</code></div>
                            <div class="mt-3"><code>update pub.portalbenutzer set portalaktiv=1 where portalbenutzer_id=1</code></div>
                            <div class="mt-3"><code>select * from pub.portalbenutzer</code></div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center mt-2" style="padding-top:2px">
                        <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDbStruct" aria-expanded="false">
                        Show DB structure
                        </button>
                        <div class="collapse mt-2" id="collapseDbStruct">
                          <div class="card card-body">
                            <div class="text-center">
                                <div class="spinner-border" role="status" id="db_struct_spinner">
                                    <span class="visually-hidden">Loading...</span>
                                  <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <div class="text-danger d-none" id="db_struct_error"></div>
                            <table class="table table-striped table-bordered d-none" id="db_struct_table">
                                <tbody>
                                </tbody>
                            </table>
                          </div>
                    </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane container-fluid<?php echo $form === 'compare' ? ' active' : ' fade' ?>" id="compare">
                <div class="row mt-3 justify-content-center">
                    <div class="col-md-4">
                        <?php if (empty($currentComparisonState)) { ?>
                            <h4>Current status: comparison not active</h4>
                            <button class="btn btn-primary" type="submit" name="action[comparison]" value="activate">
                                ACTIVATE
                            </button>
                        <?php } else if ($currentComparisonState['state'] === 'active') { ?>
                            <h4>Current status: <span class="text-warning">in progress<span></h4>
                            <button class="btn btn-danger" type="submit" name="action[comparison]" value="stop">STOP
                            </button>
                            <button class="btn btn-success" type="submit" name="action[comparison]" value="done">
                                FINISH
                            </button>
                        <?php } else if ($currentComparisonState['state'] === 'done') { ?>
                            <h4>Current status: <span class="text-success">complete<span></h4>
                            <button class="btn btn-secondary" type="submit" name="action[comparison]" value="stop">
                                RESET
                            </button>
                        <?php } ?>
                    </div>
                    <div class="col-md-8">
                        <table class="table">
                            <?php if ($currentComparisonState && $currentComparisonState['state'] === 'done') {
                                foreach ($currentComparisonState['diff'] as $name => $item) {
                                    switch ($item['type']) {
                                        case 'new':
                                            $label = ' → ' . $item['after'];
                                            $badge = '<span class="badge bg-warning">new</span>';
                                            break;
                                        case 'removed':
                                            $label = $item['before'] . ' → ';
                                            $badge = '<span class="badge bg-warning">removed</span>';
                                            break;
                                        case 'more':
                                            $label = $item['before'] . ' → ' . $item['after'];
                                            $badge = '<span class="badge bg-success">more</span>';
                                            break;
                                        case 'less':
                                            $label = $item['before'] . ' → ' . $item['after'];
                                            $badge = '<span class="badge bg-danger">less</span>';
                                            break;
                                        case 'same':
                                            $label = '<span class="text-muted">' . $item['before'] . '</span>';
                                            $badge = '<span class="badge bg-secondary">same</span>';
                                            $name = '<span class="text-muted">' . $name . '</span>';
                                            break;
                                        default:
                                            $label = '';
                                            $badge = '';
                                            break;
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $badge ?></td>
                                        <td><?php echo $name ?></td>
                                        <td class="text-end"><?php echo $label ?></td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>

<script src="./../js/db.js"></script>
<script src="./../js/bootstrap.bundle.min.js"></script>

</body>
</html>
