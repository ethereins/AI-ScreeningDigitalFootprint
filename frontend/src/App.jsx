import React from 'react'
import { Routes, Route } from 'react-router-dom'
import Layout from './components/common/layout/Layout'
import DashboardPage from './pages/DashboardPage'
import AddCandidatePage from './pages/AddCandidatePage'
import CandidateDetailPage from './pages/CandidateDetailPage'
import SettingsPage from './pages/SettingsPage'
import NotFoundPage from './pages/NotFoundPage'

function App() {
  return (
    <Layout>
      <Routes>
        <Route path="/" element={<DashboardPage />} />
        <Route path="/candidates" element={<DashboardPage />} />
        <Route path="/add" element={<AddCandidatePage />} />
        <Route path="/candidate/:id" element={<CandidateDetailPage />} />
        <Route path="/settings" element={<SettingsPage />} />
        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </Layout>
  )
}

export default App