import React from 'react';
import { Search, Bell, Menu } from 'lucide-react';

const Header = ({ sidebarOpen, setSidebarOpen }) => {
  return (
    <header className="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
      <div className="flex items-center gap-4">
        <button
          onClick={() => setSidebarOpen(!sidebarOpen)}
          className="p-2 hover:bg-gray-100 rounded-lg lg:hidden"
        >
          <Menu className="w-5 h-5 text-gray-500" />
        </button>
        <div className="flex-1 max-w-md relative">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
          <input
            type="text"
            placeholder="Search candidates..."
            className="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow outline-none"
          />
        </div>
      </div>
      <div className="flex items-center gap-3">
        <button className="p-2 hover:bg-gray-100 rounded-lg relative">
          <Bell className="w-5 h-5 text-gray-500" />
          <span className="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
        </button>
        <div className="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
          <span className="text-sm font-medium text-gray-700">AD</span>
        </div>
      </div>
    </header>
  );
};

export default Header;