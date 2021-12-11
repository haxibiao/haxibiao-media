export function errorMessage(error) {
  let content = '';
  const { graphQLErrors, networkError } = error;
  if (typeof error === 'string') {
    content = error.replace('Error: ', '');
  } else if (graphQLErrors) {
    graphQLErrors.map(({ message, locations, path }) => {
      content += `${message.replace('GraphQL error: ', '')}\n`;
    });
  } else if (networkError) {
    content = `${networkError}`;
  }
  return content || '请求失败...';
}

export function logErrorMessage({ graphQLErrors, networkError }) {
  if (graphQLErrors)
    graphQLErrors.map(({ message, locations, path }) =>
      console.log(`[GraphQL error]: Message: ${message}, Location: ${locations}, Path: ${path}`)
    );

  if (networkError) console.log(`[Network error]: ${networkError}`);
}
