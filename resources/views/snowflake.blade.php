<!DOCTYPE html>
<html>
<head>
    <title>Snowflake Test</title>
    <style>
        body { font-family: sans-serif; padding: 2rem; max-width: 800px; margin: 0 auto; }
        h1 { color: #333; }
        .error { background: #fee; border: 1px solid #c00; padding: 1rem; border-radius: 4px; color: #c00; }
        .success { background: #efe; border: 1px solid #0a0; padding: 1rem; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #ddd; padding: 0.5rem; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Snowflake Connection Test</h1>

    @if($error)
        <div class="error">
            <strong>Error:</strong> {{ $error }}
        </div>
    @else
        <div class="success">
            <strong>Connected successfully!</strong>
        </div>

        @if(count($results) > 0)
            <table>
                <thead>
                    <tr>
                        @foreach(array_keys($results[0]) as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $row)
                        <tr>
                            @foreach($row as $value)
                                <td>{{ $value }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif
</body>
</html>
