app.filter("jiraIssueUrl", function(config)
{
	return function(issuekey)
	{
		return config.uris.jiraUrlPrefix + issuekey;
	};
});