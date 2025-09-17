import React from 'react'
import { AppProvider, Card, Page, Text } from '@shopify/polaris'
import '@shopify/polaris/build/esm/styles.css'
import { SeoEditor } from '../ui/SeoEditor'

export default function App() {
  return (
    <AppProvider i18n={{}}>
      <Page title="Rush SEO">
        <Card>
          <div style={{ padding: 16 }}>
            <Text as="p">Admin UI is running.</Text>
            <SeoEditor />
          </div>
        </Card>
      </Page>
    </AppProvider>
  )
}

