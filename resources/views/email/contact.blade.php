<!DOCTYPE html>
<html>
<head>
</head>
<body>
<table cellspacing="0">
    <tbody>
    <tr>
        <td>
            <br>
            <strong>From:</strong> {{$user['contactName']}} <span><a href="mailto:{{$user['contactEmail']}}">{{$user['contactEmail']}}</a></span>
            <br>
            <strong>Subject:</strong> {{$user['contactSubject']}}
            <br>
            <strong>Message:</strong> {{$user['contactMessage']}}
        </td>
    </tr>
    </tbody>
</table>
</body>
</html>
